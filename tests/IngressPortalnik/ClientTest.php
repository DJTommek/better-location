<?php declare(strict_types=1);

namespace Tests\IngressPortalnik;

use App\Cache\NetteCachePsr16;
use App\Config;
use App\Ingress\MissionInterface;
use App\Ingress\MissionKind;
use App\Ingress\MissionProvider;
use App\Ingress\PortalProvider;
use App\Ingress\WaypointInterface;
use App\Ingress\WaypointKind;
use App\Ingress\WaypointObjective;
use App\IngressPortalnik\Client;
use App\IngressPortalnik\PortalnikException;
use GuzzleHttp\Psr7\Response;
use Nette\Caching\Storages\DevNullStorage;
use Nette\Caching\Storages\MemoryStorage;
use Nette\Utils\Json;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Tests\HttpTestClients;

final class ClientTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	#[\Override]
	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public static function universalProvider(): array
	{
		return [
			['ed64a5b3c8fc3d61973a863b3667b116.16', 'Mariánsky sloup', 50.087354, 14.421306],
			// Real portal name contains extra space: 'Era Kone Amphitheater '
			['6e6c2299f12b496cbd2c15971a306c51.16', 'Era Kone Amphitheater', -9.480218, 147.15345],
			['ddb6b3176edf4e94969f6c0db32caaf9.12', 'GANDHI MANDAPAM', 8.078457, 77.550645],
			// Portal name contains only numbers
			['470292c1672441d18585709d871f27d7.16', '1737', 49.456762, 13.784239],
		];
	}

	public static function guidNotExists(): array
	{
		return [
			['aaaabbbbccccdddd1111222233339999.16'],
		];
	}

	/**
	 * Valid coordinates, but no portal exists
	 */
	public static function coordsWithoutPortalProvider(): array
	{
		return [
			[1.12345, -9.98765],
		];
	}

	public static function nearbyProvider(): array
	{
		return [
			['ed64a5b3c8fc3d61973a863b3667b116.16', 50.087354, 14.421307],
		];
	}

	public static function missionProvider(): array
	{
		return [
			['0a3f101657ac441286a56b391f969e15.1c', 'MD 2019: Prague, Old Town Square Execution', MissionKind::AnyOrder, 7, '50.087451,14.420671', '0bd94fac5de84105b6eef6e7e1639ad9.12'],
			['b9d1285bdf184780a79f1a00b68e893e.1c', '1/6 Horseshoe Lake Second Sunday June ‘22', MissionKind::AnyOrder, 8, '40.857356,-74.636430', '0a4769e7089141c6993d27d1c9d266e6.16'],
		];
	}

	public static function missionGuidNotExists(): array
	{
		return [
			['aaaabbbbccccdddd1111222233339999.1c'],
		];
	}

	public static function missionWaypointProvider(): array
	{
		return [
			['0a3f101657ac441286a56b391f969e15.1c', 1, 'Here Albert Einstein Played the Violin', WaypointObjective::Hack, '50.087246,14.421810'],
			['493a37be3e3b4991bc083a3b815c97bc.1c', 5, 'Sir Nicholas George Winton', WaypointObjective::EnterPassphrase, '50.082440,14.435059'],
		];
	}

	public static function missionsByPortalProvider(): array
	{
		return [
			['0a3f101657ac441286a56b391f969e15.1c', '0bd94fac5de84105b6eef6e7e1639ad9.12'],
		];
	}

	public static function missionsNearbyProvider(): array
	{
		return [
			['0a3f101657ac441286a56b391f969e15.1c', 50.087451, 14.420671],
		];
	}

	public static function searchProvider(): array
	{
		return [
			['ed64a5b3c8fc3d61973a863b3667b116.16', 'Mariansky sloup'],
		];
	}

	private function createRealClient(): Client
	{
		$apiUrl = Config::getPortalnikApiUrl();
		$apiKey = Config::getPortalnikApiKey();
		if ($apiUrl === null || $apiKey === null) {
			self::markTestSkipped('Missing IvoPortalnik API URL and/or key');
		}
		return $this->createClient($this->httpTestClients->realHttpClient, $apiUrl, $apiKey);
	}

	/**
	 * Saved responses are stored based on API URL, so it must be configured even for offline tests. API key is not
	 * needed, it is not part of saved response fingerprint.
	 */
	private function createOfflineClient(): Client
	{
		$apiUrl = Config::getPortalnikApiUrl();
		if ($apiUrl === null) {
			self::markTestSkipped('Missing IvoPortalnik API URL');
		}
		return $this->createClient($this->httpTestClients->offlineHttpClient, $apiUrl, '');
	}

	private function createClient(
		ClientInterface $httpClient,
		string $apiUrl = 'https://portalnik.example.com',
		string $apiKey = 'secret-token',
		?int $cacheTtl = null,
		bool $memoryCache = false,
	): Client {
		return new Client(
			httpClient: $httpClient,
			cache: new NetteCachePsr16($memoryCache ? new MemoryStorage() : new DevNullStorage()),
			apiUrl: $apiUrl,
			apiKey: $apiKey,
			cacheTtl: $cacheTtl,
		);
	}

	public function testImplementsPortalProvider(): void
	{
		$this->assertInstanceOf(PortalProvider::class, $this->createClient($this->httpTestClients->mockedHttpClient));
	}

	public function testImplementsMissionProvider(): void
	{
		$this->assertInstanceOf(MissionProvider::class, $this->createClient($this->httpTestClients->mockedHttpClient));
	}

	/**
	 * @group request
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByCoordsReal(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$this->testGetPortalByCoords($this->createRealClient(), $expectedGuid, $expectedName, $lat, $lon);
	}

	/**
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByCoordsOffline(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$this->testGetPortalByCoords($this->createOfflineClient(), $expectedGuid, $expectedName, $lat, $lon);
	}

	private function testGetPortalByCoords(Client $client, string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$portal = $client->getPortalByCoords($lat, $lon);
		$this->assertSame($expectedGuid, $portal->getGuid());
		$this->assertSame($expectedName, $portal->getName());
		$this->assertSame($lat, $portal->getLat());
		$this->assertSame($lon, $portal->getLon());
	}

	/**
	 * @group request
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByGuidReal(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$this->testGetPortalByGuid($this->createRealClient(), $expectedGuid, $expectedName, $lat, $lon);
	}

	/**
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByGuidOffline(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$this->testGetPortalByGuid($this->createOfflineClient(), $expectedGuid, $expectedName, $lat, $lon);
	}

	private function testGetPortalByGuid(Client $client, string $guid, string $expectedName, float $expectedLat, float $expectedLon): void
	{
		$portal = $client->getPortalByGUID($guid);
		$this->assertSame($guid, $portal->getGuid());
		$this->assertSame($expectedName, $portal->getName());
		$this->assertSame($expectedLat, $portal->getLat());
		$this->assertSame($expectedLon, $portal->getLon());
	}

	/**
	 * @group request
	 * @dataProvider guidNotExists
	 */
	public function testGetPortalByGuidInvalidReal(string $guid): void
	{
		$this->assertNull($this->createRealClient()->getPortalByGUID($guid));
	}

	/**
	 * @dataProvider guidNotExists
	 */
	public function testGetPortalByGuidInvalidOffline(string $guid): void
	{
		$this->assertNull($this->createOfflineClient()->getPortalByGUID($guid));
	}

	/**
	 * @group request
	 * @dataProvider coordsWithoutPortalProvider
	 */
	public function testGetPortalByInvalidCoordsReal(float $lat, float $lon): void
	{
		$this->assertNull($this->createRealClient()->getPortalByCoords($lat, $lon));
	}

	/**
	 * @dataProvider coordsWithoutPortalProvider
	 */
	public function testGetPortalByInvalidCoordsOffline(float $lat, float $lon): void
	{
		$this->assertNull($this->createOfflineClient()->getPortalByCoords($lat, $lon));
	}

	/**
	 * @group request
	 * @dataProvider nearbyProvider
	 */
	public function testGetPortalsNearbyReal(string $expectedFirstGuid, float $lat, float $lon): void
	{
		$this->testGetPortalsNearby($this->createRealClient(), $expectedFirstGuid, $lat, $lon);
	}

	/**
	 * @dataProvider nearbyProvider
	 */
	public function testGetPortalsNearbyOffline(string $expectedFirstGuid, float $lat, float $lon): void
	{
		$this->testGetPortalsNearby($this->createOfflineClient(), $expectedFirstGuid, $lat, $lon);
	}

	private function testGetPortalsNearby(Client $client, string $expectedFirstGuid, float $lat, float $lon): void
	{
		$portals = $client->getPortalsNearby($lat, $lon, radius: 100, limit: 10);
		$this->assertNotEmpty($portals);
		$this->assertLessThanOrEqual(10, count($portals));
		$this->assertSame($expectedFirstGuid, $portals[0]->getGuid());
	}

	/**
	 * @group request
	 * @dataProvider searchProvider
	 */
	public function testSearchPortalsReal(string $expectedGuid, string $query): void
	{
		$this->testSearchPortals($this->createRealClient(), $expectedGuid, $query);
	}

	/**
	 * @dataProvider searchProvider
	 */
	public function testSearchPortalsOffline(string $expectedGuid, string $query): void
	{
		$this->testSearchPortals($this->createOfflineClient(), $expectedGuid, $query);
	}

	private function testSearchPortals(Client $client, string $expectedGuid, string $query): void
	{
		$portals = $client->searchPortals($query);
		$guids = array_map(fn($portal) => $portal->getGuid(), $portals);
		$this->assertContains($expectedGuid, $guids);
	}

	/**
	 * Saved responses are fingerprinted without Authorization header, so this test must request unique URL, otherwise
	 * it would overwrite saved response of another test.
	 *
	 * @group request
	 */
	public function testInvalidApiKeyReal(): void
	{
		$apiUrl = Config::getPortalnikApiUrl();
		if ($apiUrl === null || !Config::isPortalnik()) {
			self::markTestSkipped('Missing IvoPortalnik API URL and/or key');
		}
		$client = $this->createClient($this->httpTestClients->realHttpClient, $apiUrl, 'invalid-api-key');

		$this->expectException(PortalnikException::class);
		$this->expectExceptionCode(401);
		$client->searchPortals('invalid api key test', limit: 1);
	}

	/**
	 * Behavior, that can't be reliably tested with real API: caching and error handling
	 */
	public function testUnidentifiedPortalWithRetryIsNotCachedMocked(): void
	{
		$client = $this->createClient($this->httpTestClients->mockedHttpClient, cacheTtl: 60, memoryCache: true);
		$portal = [
			'guid' => 'ed64a5b3c8fc3d61973a863b3667b116.16',
			'lat' => 50.087354,
			'lng' => 14.421307,
			'name' => 'Mariánsky sloup',
			'image_url' => null,
			'address' => null,
			'first_seen_at' => '2020-01-02T03:04:05.000Z',
			'last_seen_at' => '2026-11-20T10:00:00.000Z',
			'updated_at' => '2025-06-01T12:00:00.000Z',
			'links' => ['intel' => '', 'prime' => ''],
		];
		$this->appendMockedJson([
			'portal' => null,
			'confident' => false,
			'candidates' => [],
			'meta' => ['upstream' => 'queued', 'retry_after_ms' => 2000, 'hint' => null],
		]);
		$this->appendMockedJson([
			'portal' => $portal,
			'confident' => true,
			'candidates' => [],
			'meta' => ['upstream' => 'not_needed', 'retry_after_ms' => null, 'hint' => null],
		]);

		$this->assertNull($client->getPortalByCoords(50.087354, 14.421307));
		$this->assertNotNull($client->getPortalByCoords(50.087354, 14.421307));
		// Now it is cached, no more mocked responses are available
		$this->assertNotNull($client->getPortalByCoords(50.087354, 14.421307));
		$this->assertSame(0, $this->httpTestClients->mockHandler->count());
	}

	public function testServerErrorMocked(): void
	{
		$client = $this->createClient($this->httpTestClients->mockedHttpClient);
		$this->appendMockedJson(['type' => 'about:blank', 'title' => 'Service Unavailable', 'status' => 503, 'detail' => 'Try again later'], 503);

		$this->expectException(PortalnikException::class);
		$this->expectExceptionMessage('failed with HTTP 503: Service Unavailable (Try again later)');
		$client->getPortalByGUID('ed64a5b3c8fc3d61973a863b3667b116.16');
	}

	/**
	 * @group request
	 * @dataProvider missionProvider
	 */
	public function testGetMissionByGuidReal(string $guid, string $expectedTitle, MissionKind $expectedKind, int $expectedWaypointsCount, string $expectedStartLatLon, string $expectedStartPortalGuid): void
	{
		$this->testGetMissionByGuid($this->createRealClient(), $guid, $expectedTitle, $expectedKind, $expectedWaypointsCount, $expectedStartLatLon, $expectedStartPortalGuid);
	}

	/**
	 * @dataProvider missionProvider
	 */
	public function testGetMissionByGuidOffline(string $guid, string $expectedTitle, MissionKind $expectedKind, int $expectedWaypointsCount, string $expectedStartLatLon, string $expectedStartPortalGuid): void
	{
		$this->testGetMissionByGuid($this->createOfflineClient(), $guid, $expectedTitle, $expectedKind, $expectedWaypointsCount, $expectedStartLatLon, $expectedStartPortalGuid);
	}

	private function testGetMissionByGuid(Client $client, string $guid, string $expectedTitle, MissionKind $expectedKind, int $expectedWaypointsCount, string $expectedStartLatLon, string $expectedStartPortalGuid): void
	{
		$mission = $client->getMissionByGUID($guid);
		$this->assertSame($guid, $mission->getGuid());
		$this->assertSame($expectedTitle, $mission->getTitle());
		$this->assertSame($expectedKind, $mission->getKind());
		$this->assertTrue($mission->hasDetails);
		$this->assertStringStartsWith('https://lh3.googleusercontent.com/', $mission->getImageLink());
		$this->assertSame('https://intel.ingress.com/mission/' . $guid, $mission->getIntelLink());

		$waypoints = $mission->getWaypoints();
		$this->assertCount($expectedWaypointsCount, $waypoints);
		$this->assertSame(range(0, $expectedWaypointsCount - 1), array_map(fn(WaypointInterface $waypoint) => $waypoint->getPosition(), $waypoints));

		$start = $mission->getStartWaypoint();
		$this->assertSame(0, $start->getPosition());
		$this->assertSame(WaypointKind::Portal, $start->getKind());
		$this->assertSame($expectedStartLatLon, $start->getCoordinates()->getLatLon());
		$this->assertSame($expectedStartPortalGuid, $start->getPortalGuid());
	}

	/**
	 * @group request
	 * @dataProvider missionGuidNotExists
	 */
	public function testGetMissionByGuidInvalidReal(string $guid): void
	{
		$this->assertNull($this->createRealClient()->getMissionByGUID($guid));
	}

	/**
	 * @dataProvider missionGuidNotExists
	 */
	public function testGetMissionByGuidInvalidOffline(string $guid): void
	{
		$this->assertNull($this->createOfflineClient()->getMissionByGUID($guid));
	}

	/**
	 * @group request
	 * @dataProvider missionWaypointProvider
	 */
	public function testGetMissionWaypointReal(string $missionGuid, int $position, string $expectedTitle, WaypointObjective $expectedObjective, string $expectedLatLon): void
	{
		$this->testGetMissionWaypoint($this->createRealClient(), $missionGuid, $position, $expectedTitle, $expectedObjective, $expectedLatLon);
	}

	/**
	 * @dataProvider missionWaypointProvider
	 */
	public function testGetMissionWaypointOffline(string $missionGuid, int $position, string $expectedTitle, WaypointObjective $expectedObjective, string $expectedLatLon): void
	{
		$this->testGetMissionWaypoint($this->createOfflineClient(), $missionGuid, $position, $expectedTitle, $expectedObjective, $expectedLatLon);
	}

	private function testGetMissionWaypoint(Client $client, string $missionGuid, int $position, string $expectedTitle, WaypointObjective $expectedObjective, string $expectedLatLon): void
	{
		$waypoint = $client->getMissionWaypoint($missionGuid, $position);
		$this->assertSame($position, $waypoint->getPosition());
		$this->assertSame($expectedTitle, $waypoint->getTitle());
		$this->assertSame($expectedObjective, $waypoint->getObjective());
		$this->assertFalse($waypoint->isHidden());
		$this->assertSame($expectedLatLon, $waypoint->getCoordinates()->getLatLon());
	}

	/**
	 * @group request
	 */
	public function testGetMissionWaypointInvalidReal(): void
	{
		$this->assertNull($this->createRealClient()->getMissionWaypoint('0a3f101657ac441286a56b391f969e15.1c', 999));
	}

	public function testGetMissionWaypointInvalidOffline(): void
	{
		$this->assertNull($this->createOfflineClient()->getMissionWaypoint('0a3f101657ac441286a56b391f969e15.1c', 999));
	}

	public function testGetMissionWaypointInvalidPosition(): void
	{
		$client = $this->createClient($this->httpTestClients->mockedHttpClient);

		$this->expectException(\InvalidArgumentException::class);
		$client->getMissionWaypoint('0a3f101657ac441286a56b391f969e15.1c', -1);
	}

	/**
	 * @group request
	 * @dataProvider missionsByPortalProvider
	 */
	public function testGetMissionsByPortalReal(string $expectedMissionGuid, string $portalGuid): void
	{
		$this->testGetMissionsByPortal($this->createRealClient(), $expectedMissionGuid, $portalGuid);
	}

	/**
	 * @dataProvider missionsByPortalProvider
	 */
	public function testGetMissionsByPortalOffline(string $expectedMissionGuid, string $portalGuid): void
	{
		$this->testGetMissionsByPortal($this->createOfflineClient(), $expectedMissionGuid, $portalGuid);
	}

	private function testGetMissionsByPortal(Client $client, string $expectedMissionGuid, string $portalGuid): void
	{
		$missions = $client->getMissionsByPortal($portalGuid);
		$guids = array_map(fn(MissionInterface $mission) => $mission->getGuid(), $missions);
		$this->assertContains($expectedMissionGuid, $guids);
		$this->assertSame([], $missions[0]->getWaypoints(), 'Missions from list endpoint are without waypoints.');
	}

	/**
	 * @group request
	 * @dataProvider guidNotExists
	 */
	public function testGetMissionsByPortalInvalidReal(string $portalGuid): void
	{
		$this->assertNull($this->createRealClient()->getMissionsByPortal($portalGuid));
	}

	/**
	 * @dataProvider guidNotExists
	 */
	public function testGetMissionsByPortalInvalidOffline(string $portalGuid): void
	{
		$this->assertNull($this->createOfflineClient()->getMissionsByPortal($portalGuid));
	}

	/**
	 * @group request
	 * @dataProvider missionsNearbyProvider
	 */
	public function testGetMissionsNearbyReal(string $expectedMissionGuid, float $lat, float $lon): void
	{
		$this->testGetMissionsNearby($this->createRealClient(), $expectedMissionGuid, $lat, $lon);
	}

	/**
	 * @dataProvider missionsNearbyProvider
	 */
	public function testGetMissionsNearbyOffline(string $expectedMissionGuid, float $lat, float $lon): void
	{
		$this->testGetMissionsNearby($this->createOfflineClient(), $expectedMissionGuid, $lat, $lon);
	}

	private function testGetMissionsNearby(Client $client, string $expectedMissionGuid, float $lat, float $lon): void
	{
		$missions = $client->getMissionsNearby($lat, $lon, radius: 50, limit: 50);
		$this->assertLessThanOrEqual(50, count($missions));
		$guids = array_map(fn(MissionInterface $mission) => $mission->getGuid(), $missions);
		$this->assertContains($expectedMissionGuid, $guids);
	}

	public function testGetMissionsNearbyInvalidRadius(): void
	{
		$client = $this->createClient($this->httpTestClients->mockedHttpClient);

		$this->expectException(\InvalidArgumentException::class);
		$client->getMissionsNearby(50.087451, 14.420671, radius: 20001);
	}

	private function appendMockedJson(array $data, int $status = 200): void
	{
		$this->httpTestClients->mockHandler->append(new Response($status, ['Content-Type' => 'application/json'], Json::encode($data)));
	}
}
