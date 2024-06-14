<?php declare(strict_types=1);

namespace Tests\IngressLanchedRu\Types;

use App\IngressLanchedRu\Client;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;

final class ClientTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public static function universalProvider(): array
	{
		return [
			['ed64a5b3c8fc3d61973a863b3667b116.16', 'Mariánsky sloup', 50.087354, 14.421307],
			['6e6c2299f12b496cbd2c15971a306c51.16', 'Era Kone Amphitheater', -9.480218, 147.15345],
			['ddb6b3176edf4e94969f6c0db32caaf9.12', 'GANDHI MANDAPAM', 8.078457, 77.550645],
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

	/**
	 * @group request
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByCoordsReal(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->realRequestor);
		$this->testGetPortalByCoords($client, $expectedGuid, $expectedName, $lat, $lon);
	}

	/**
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByCoordsOffline(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->offlineRequestor);
		$this->testGetPortalByCoords($client, $expectedGuid, $expectedName, $lat, $lon);
	}

	private function testGetPortalByCoords(Client $client, string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$portal = $client->getPortalByCoords($lat, $lon);
		$this->assertSame($expectedGuid, $portal->guid);
		$this->assertSame($expectedName, $portal->name);
		$this->assertSame($lat, $portal->lat);
		$this->assertSame($lon, $portal->lng);
	}

	/**
	 * @group request
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByGuidReal(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->realRequestor);
		$this->testGetPortalByGuid($client, $expectedGuid, $expectedName, $lat, $lon);
	}

	/**
	 * @dataProvider universalProvider
	 */
	public function testGetPortalByGuidOffline(string $expectedGuid, string $expectedName, float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->offlineRequestor);
		$this->testGetPortalByGuid($client, $expectedGuid, $expectedName, $lat, $lon);
	}

	private function testGetPortalByGuid(Client $client, string $guid, string $expectedName, float $expectedLat, float $expectedLon): void
	{
		$portal = $client->getPortalByGUID($guid);
		$this->assertSame($guid, $portal->guid);
		$this->assertSame($expectedName, $portal->name);
		$this->assertSame($expectedLat, $portal->lat);
		$this->assertSame($expectedLon, $portal->lng);
	}

	/**
	 * @group request
	 * @dataProvider guidNotExists
	 */
	public function testGetPortalByGuidInvalidReal(string $guid): void
	{
		$client = new Client($this->httpTestClients->realRequestor);
		$this->assertNull($client->getPortalByGUID($guid));
	}

	/**
	 * @dataProvider guidNotExists
	 */
	public function testGetPortalByGuidInvalidOffline(string $guid): void
	{
		$client = new Client($this->httpTestClients->offlineRequestor);
		$this->assertNull($client->getPortalByGUID($guid));
	}

	/**
	 * @group request
	 * @dataProvider coordsWithoutPortalProvider
	 */
	public function testGetPortalByInvalidCoordsReal(float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->realRequestor);
		$this->assertNull($client->getPortalByCoords($lat, $lon));
	}

	/**
	 * @dataProvider coordsWithoutPortalProvider
	 */
	public function testGetPortalByInvalidCoordsOffline(float $lat, float $lon): void
	{
		$client = new Client($this->httpTestClients->offlineRequestor);
		$this->assertNull($client->getPortalByCoords($lat, $lon));
	}
}
