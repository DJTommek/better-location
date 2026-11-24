<?php declare(strict_types=1);

namespace App\IngressPortalnik;

use App\Ingress\MissionProvider;
use App\Ingress\PortalProvider;
use App\IngressPortalnik\Types\MissionType;
use App\IngressPortalnik\Types\PortalType;
use App\IngressPortalnik\Types\WaypointType;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * IvoPortalnik API - database and caching proxy of Ingress portals and missions.
 *
 * Coordinates in responses are decimal degrees, all times are UTC.
 *
 * @see ivo-portalnik-api-0.2.1.json OpenAPI definition
 */
class Client implements PortalProvider, MissionProvider
{
	private const string PATH_PORTALS_IDENTIFY = '/api/v1/portals/identify';
	private const string PATH_PORTALS_NEARBY = '/api/v1/portals/nearby';
	private const string PATH_PORTALS_SEARCH = '/api/v1/portals/search';
	private const string PATH_PORTAL = '/api/v1/portals/%s';
	private const string PATH_PORTAL_MISSIONS = '/api/v1/portals/%s/missions';
	private const string PATH_MISSIONS_NEARBY = '/api/v1/missions/nearby';
	private const string PATH_MISSION = '/api/v1/missions/%s';
	private const string PATH_MISSION_WAYPOINT = '/api/v1/missions/%s/waypoints/%d';

	private readonly string $apiUrl;

	/**
	 * @param string $apiUrl Base URL of API, without "/api/v1" path, eg. "https://portalnik.example.com"
	 * @param string $apiKey API token, sent as "Authorization: Bearer <token>"
	 * @param ?int $cacheTtl Cache successful responses (and "not found" responses) for this amount of seconds, null to disable
	 * @param IdentifyUpstream $identifyUpstream What to do in getPortalByCoords() when portal is not known locally
	 */
	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly CacheInterface $cache,
		string $apiUrl,
		#[\SensitiveParameter] private readonly string $apiKey,
		private readonly ?int $cacheTtl = null,
		private readonly IdentifyUpstream $identifyUpstream = IdentifyUpstream::Wait,
	) {
		$this->apiUrl = rtrim($apiUrl, '/');
	}

	#[\Override]
	public function getPortalByCoords(float $lat, float $lng): ?PortalType
	{
		return $this->identifyPortal(lat: $lat, lng: $lng);
	}

	#[\Override]
	public function getPortalByGUID(string $guid): ?PortalType
	{
		$json = $this->request(sprintf(self::PATH_PORTAL, rawurlencode($guid)));
		if ($json === null) {
			return null;
		}
		return PortalType::createFromVariable($json->portal);
	}

	/**
	 * Identify one specific portal from coordinates, name and address. At least coordinates or name must be filled.
	 *
	 * @return ?PortalType Null if portal can't be identified reliably.
	 */
	public function identifyPortal(
		?float $lat = null,
		?float $lng = null,
		?string $name = null,
		?string $address = null,
		?IdentifyUpstream $upstream = null,
	): ?PortalType {
		if (($lat === null) !== ($lng === null)) {
			throw new \InvalidArgumentException('Both latitude and longitude must be filled or both must be null.');
		}
		if ($lat === null && $name === null) {
			throw new \InvalidArgumentException('At least coordinates or name must be filled.');
		}

		$params = array_filter([
			'lat' => $lat,
			'lng' => $lng,
			'name' => $name,
			'address' => $address,
			'upstream' => ($upstream ?? $this->identifyUpstream)->value,
		], fn(mixed $value) => $value !== null);

		$json = $this->request(
			self::PATH_PORTALS_IDENTIFY,
			$params,
			// Do not cache unidentified portal, if API says that asking again later might help
			isCacheable: fn(\stdClass $json): bool => $json->portal !== null || ($json->meta->retry_after_ms ?? null) === null,
		);
		if ($json === null || $json->portal === null) {
			return null;
		}
		return PortalType::createFromVariable($json->portal);
	}

	/**
	 * All known portals in an area, nearest first. Local database of API only.
	 *
	 * @param int $radius Meters (1 - 5000)
	 * @param int $limit 1 - 200
	 * @return list<PortalType>
	 */
	public function getPortalsNearby(float $lat, float $lng, int $radius = 100, int $limit = 50): array
	{
		if ($radius < 1 || $radius > 5000) {
			throw new \InvalidArgumentException(sprintf('Parameter "radius" (%d) must be between 1 and 5000.', $radius));
		}
		if ($limit < 1 || $limit > 200) {
			throw new \InvalidArgumentException(sprintf('Parameter "limit" (%d) must be between 1 and 200.', $limit));
		}

		$json = $this->request(self::PATH_PORTALS_NEARBY, [
			'lat' => $lat,
			'lng' => $lng,
			'radius' => $radius,
			'limit' => $limit,
		]);
		$result = [];
		foreach ($json->portals ?? [] as $item) {
			$result[] = PortalType::createFromVariable($item->portal);
		}
		return $result;
	}

	/**
	 * Search portals by name or address. All words must match (as word prefixes), diacritics and case are ignored.
	 *
	 * @param int $limit 1 - 100
	 * @return list<PortalType>
	 */
	public function searchPortals(string $query, int $limit = 20): array
	{
		if ($limit < 1 || $limit > 100) {
			throw new \InvalidArgumentException(sprintf('Parameter "limit" (%d) must be between 1 and 100.', $limit));
		}

		$json = $this->request(self::PATH_PORTALS_SEARCH, [
			'q' => $query,
			'limit' => $limit,
		]);
		$result = [];
		foreach ($json->portals ?? [] as $portalData) {
			$result[] = PortalType::createFromVariable($portalData);
		}
		return $result;
	}

	/**
	 * Mission with its waypoints (if known, see MissionType::$hasDetails).
	 *
	 * @param string $guid Ingress mission GUID, eg. "b9d1285bdf184780a79f1a00b68e893e.1c"
	 * @return ?MissionType Null if mission is not in local database of API.
	 */
	#[\Override]
	public function getMissionByGUID(string $guid): ?MissionType
	{
		$json = $this->request(sprintf(self::PATH_MISSION, rawurlencode($guid)));
		if ($json === null) {
			return null;
		}
		return MissionType::createFromVariable($json->mission, $json->waypoints ?? []);
	}

	/**
	 * Single waypoint of a mission.
	 *
	 * @param int $position 0-based (0 - 1000)
	 * @return ?WaypointType Null if mission or waypoint is not found.
	 */
	public function getMissionWaypoint(string $missionGuid, int $position): ?WaypointType
	{
		if ($position < 0 || $position > 1000) {
			throw new \InvalidArgumentException(sprintf('Parameter "position" (%d) must be between 0 and 1000.', $position));
		}

		$json = $this->request(sprintf(self::PATH_MISSION_WAYPOINT, rawurlencode($missionGuid), $position));
		if ($json === null) {
			return null;
		}
		return WaypointType::createFromVariable($json->waypoint);
	}

	/**
	 * Missions with a waypoint at given portal. Missions are without waypoints.
	 *
	 * @return ?list<MissionType> Null if portal is not in local database of API.
	 */
	public function getMissionsByPortal(string $portalGuid): ?array
	{
		$json = $this->request(sprintf(self::PATH_PORTAL_MISSIONS, rawurlencode($portalGuid)));
		if ($json === null) {
			return null;
		}
		$result = [];
		foreach ($json->missions as $missionData) {
			$result[] = MissionType::createFromVariable($missionData);
		}
		return $result;
	}

	/**
	 * Missions with a waypoint within a radius, sorted by the distance of the nearest waypoint. Only missions whose
	 * detail (waypoints) is known, but returned missions are without waypoints.
	 *
	 * @param int $radius Meters (1 - 20000)
	 * @param int $limit 1 - 200
	 * @return list<MissionType>
	 */
	public function getMissionsNearby(float $lat, float $lng, int $radius = 1000, int $limit = 50): array
	{
		if ($radius < 1 || $radius > 20000) {
			throw new \InvalidArgumentException(sprintf('Parameter "radius" (%d) must be between 1 and 20000.', $radius));
		}
		if ($limit < 1 || $limit > 200) {
			throw new \InvalidArgumentException(sprintf('Parameter "limit" (%d) must be between 1 and 200.', $limit));
		}

		$json = $this->request(self::PATH_MISSIONS_NEARBY, [
			'lat' => $lat,
			'lng' => $lng,
			'radius' => $radius,
			'limit' => $limit,
		]);
		$result = [];
		foreach ($json->missions ?? [] as $item) {
			$result[] = MissionType::createFromVariable($item->mission);
		}
		return $result;
	}

	/**
	 * @param array<string, string|int|float> $query
	 * @param ?\Closure(\stdClass): bool $isCacheable Decide, if successful response can be cached. All successful responses are cached if null.
	 * @return ?\stdClass Decoded response body or null if API responded with 404 Not found.
	 * @throws PortalnikException
	 */
	private function request(string $path, array $query = [], ?\Closure $isCacheable = null): ?\stdClass
	{
		$url = $this->apiUrl . $path;
		if ($query !== []) {
			$url .= '?' . http_build_query($query);
		}

		$cacheKey = md5(self::class . $url);
		if ($this->cacheTtl !== null) {
			$cached = $this->cache->get($cacheKey);
			if ($cached !== null) {
				return $cached === false ? null : Json::decode($cached);
			}
		}

		$request = new \GuzzleHttp\Psr7\Request('GET', $url, [
			'Authorization' => 'Bearer ' . $this->apiKey,
			'Accept' => 'application/json',
		]);
		$response = $this->httpClient->sendRequest($request);
		$status = $response->getStatusCode();
		$body = (string)$response->getBody();

		if ($status === 404) {
			if ($this->cacheTtl !== null) {
				$this->cache->set($cacheKey, false, $this->cacheTtl);
			}
			return null;
		}

		if ($status !== 200) {
			throw new PortalnikException(
				sprintf('IvoPortalnik API request "%s" failed with HTTP %d: %s', $path, $status, $this->problemDetail($body)),
				$status,
			);
		}

		try {
			$json = Json::decode($body);
		} catch (JsonException $exception) {
			throw new PortalnikException(sprintf('IvoPortalnik API request "%s" returned invalid JSON.', $path), $status, $exception);
		}
		if (!$json instanceof \stdClass) {
			throw new PortalnikException(sprintf('IvoPortalnik API request "%s" returned unexpected response.', $path), $status);
		}

		if ($this->cacheTtl !== null && ($isCacheable === null || $isCacheable($json))) {
			$this->cache->set($cacheKey, $body, $this->cacheTtl);
		}

		return $json;
	}

	/**
	 * Extract human-readable message from RFC 9457 problem details response.
	 */
	private function problemDetail(string $body): string
	{
		try {
			$problem = Json::decode($body);
		} catch (JsonException) {
			return mb_substr($body, 0, 200);
		}
		if (!$problem instanceof \stdClass) {
			return mb_substr($body, 0, 200);
		}

		$title = $problem->title ?? 'Unknown error';
		$detail = $problem->detail ?? null;
		return $detail === null ? $title : sprintf('%s (%s)', $title, $detail);
	}
}
