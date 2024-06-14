<?php declare(strict_types=1);

namespace App\IngressLanchedRu;

use App\IngressLanchedRu\Types\PortalType;
use App\Utils\Requestor;

/**
 * Hi there. Looks you are interested in Ingress portal search. I can help you with it.
 * But, first you need to understand some rules.
 * First of all, don't use TOR. Use your real IP. It's faster. I will not tell anyone your IP, but i want to have ability to ban anyone.
 * Second, do not download all my database. You don't need it. You always may use search with my API, and portals database is updating every minute.
 * Third, if you have any new portal related information - you may share it with me.
 * Next, if you want to get information about new portals in your area - take a look at @IngressPortalBot. It can do that in telegram.
 * Finally, you may contact with me @Lanched.
 *
 * @see https://lanched.ru/PortalGet/
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/ Author of this PHP wrapper only, not related to API in any way.
 */
class Client
{
	const LINK = 'https://lanched.ru/PortalGet';
	const LINK_INGRESS_INTEL = 'https://intel.ingress.com';

	const LINK_GET_PORTALS = self::LINK . '/getPortals.php';
	const LINK_SEARCH_PORTALS = self::LINK . '/searchPortals.php';

	public function __construct(
		private readonly Requestor $requestor,
		private readonly ?int $cacheTtl = null,
	) {
	}

	/**
	 * Script allows getting portals in selected area (box).
	 *
	 * @param float $neLat North border latitude
	 * @param float $neLng East border longitude
	 * @param float $swLat South border latitude
	 * @param float $swLng West border longitude
	 * @param bool $telegram returns also addresses and images. Also, limits return in 50 results - its useful for using in telegram bots.
	 * @param int $offset current offset. By default, API gives 1000 portals per request.
	 * @return PortalType[]
	 */
	public function getPortals(float $neLat, float $neLng, float $swLat, float $swLng, bool $telegram = false, int $offset = 0): array
	{
		if ($neLat <= $swLat) {
			throw new \InvalidArgumentException(sprintf('Parameter "neLat" (%F) must be higher than "swLat" (%F).', $neLat, $swLat));
		}
		if ($neLng <= $swLng) {
			throw new \InvalidArgumentException(sprintf('Parameter "neLng" (%F) must be higher than "swLng" (%F).', $neLng, $swLng));
		}
		if ($offset < 0) {
			throw new \InvalidArgumentException(sprintf('Parameter "offset" (%d) must be higher or equal to zero.', $offset));
		}
		$params = [
			'nelat' => $neLat,
			'nelng' => $neLng,
			'swlat' => $swLat,
			'swlng' => $swLng,
			'offset' => $offset,
		];
		if ($telegram) {
			$params['telegram'] = '';
		}
		$url = self::LINK_GET_PORTALS . '?' . http_build_query($params);
		$json = $this->makeJsonRequest($url);
		$result = [];
		foreach ($json->portalData as $portalData) {
			$result[] = PortalType::createFromVariable($portalData);
		}
		return $result;
	}

	/**
	 * Script allows searching portal near selected point
	 *
	 * @param ?float $lat latitude of selected point
	 * @param ?float $lng longitude of selected point
	 * @param ?string $query search string. Search string can be - portal GUID, Intel link, full name, part of name or part of name\full name with part of address of that portal.
	 * @param int $offset search offset
	 * @return PortalType[]
	 */
	public function searchPortals(?float $lat, ?float $lng, ?string $query = null, int $offset = 0): array
	{
		if (is_null($lat) && is_null($lng) && is_null($query)) {
			throw new \InvalidArgumentException('At least coordinates or query must be filled');
		}
		if ($offset < 0) {
			throw new \InvalidArgumentException(sprintf('Parameter "offset" must be higher or equal to zero (%d)', $offset));
		}
		$params = [
			'lat' => $lat,
			'lng' => $lng,
			'query' => $query,
			'offset' => $offset,
		];
		$url = self::LINK_SEARCH_PORTALS . '?' . http_build_query($params);
		$json = $this->makeJsonRequest($url); // json might be null if no portal was found
		$result = [];
		foreach ($json ?? [] as $portalData) {
			$result[] = PortalType::createFromVariable($portalData);
		}
		return $result;
	}

	/** Shortcut to searchPortals() */
	public function getPortalByCoords(float $lat, float $lng): ?PortalType
	{
		$query = sprintf('%F,%F', $lat, $lng);
		$portals = $this->searchPortals($lat, $lng, $query);
		if (count($portals) === 0) {
			return null;
		} else {
			return $portals[0];
		}
	}

	/** Shortcut to searchPortals() */
	public function getPortalByGUID(string $guid): ?PortalType
	{
		$portals = $this->searchPortals(null, null, $guid);
		if (count($portals) === 0) {
			return null;
		} else {
			return $portals[0];
		}
	}

	/** @return \stdClass|array<mixed>|null */
	private function makeJsonRequest(string $url)
	{
		return $this->requestor->getJson($url, $this->cacheTtl);
	}
}
