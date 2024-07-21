<?php declare(strict_types=1);

namespace Tests\Address;

use App\Address\AddressInterface;
use App\Address\AddressProvider;
use App\Address\UniversalAddressProvider;
use App\Config;
use App\Google\Geocoding\StaticApi;
use App\Nominatim\NominatimWrapper;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use GuzzleHttp\Client;
use maxh\Nominatim\Nominatim;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use Tests\TestUtils;

final class UniversalAddressProviderTest extends TestCase
{
	public function onlyNominatimProvider(): array
	{
		return [
			['Staré Město, Praha 1, Praha, obvod Praha 1, Hlavní město Praha, Praha, 110 00, Česko', 50.087451, 14.420671], // e22583fef0f7b63a73871461adff2bb5
			['Paseo Monumento de la Paz, Barrio Cerro Juana Lainez, Distrito Morazán, Comayagüela, Tegucigalpa, Distrito Central, Francisco Morazán, 12101, Honduras', 14.0937236, -87.2011283],// f1ba43d4b9c5ad1500253e5084751a84
			['千代田, 千代田区, 東京都, 100-0001, 日本', 35.6863908, 139.7558706],// e3dbba5cf3bd9023ea2a651814d54ec0
			'Atlantic ocean' => [null, 43.8104233, -35.1563222],// 51e3fd97d8d46a0f58371b8632e52c01
		];
	}

	public function onlyGoogleProvider(): array
	{
		return [
			['Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia', 50.087451, 14.420671],
			['Monumento a la Paz - Cerro Juana Lainez, CESCCO, 1 P.º Juana Lainez, Tegucigalpa, Francisco Morazán, Honduras', 14.0937236, -87.2011283],
			['Site of Edo Castle Honmaru (Main Hall), 1 Chiyoda, Chiyoda City, Tokyo 100-0001, Japan', 35.6863908, 139.7558706],
			'Atlantic ocean' => ['89M6RR6V+5F', 43.8104233, -35.1563222],
		];
	}

	/**
	 * @group request
	 * @dataProvider onlyGoogleProvider
	 */
	public final function testBasicReal(?string $expectedAddress, float $lat, float $lon): void
	{
		$coordinates = new Coordinates($lat, $lon);
		$provider = new UniversalAddressProvider(
			google: $this->createGoogleClientReal(),
			nominatim: $this->createNominatimClientReal(),
		);
		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	/**
	 * @dataProvider onlyGoogleProvider
	 */
	public final function testBasicOfflineMocked(?string $expectedAddress, float $lat, float $lon): void
	{
		$coordinates = new Coordinates($lat, $lon);
		$provider = new UniversalAddressProvider(
			google: $this->createGoogleClientOffline(),
			nominatim: $this->createNominatimClientMocked('this should not be used'),
		);
		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	/**
	 * @group request
	 * @dataProvider onlyNominatimProvider
	 */
	public final function testOnlyNominatimReal(?string $expectedAddress, float $lat, float $lon): void
	{
		$coordinates = new Coordinates($lat, $lon);
		$provider = new UniversalAddressProvider(
			google: null,
			nominatim: $this->createNominatimClientReal(),
		);
		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	/**
	 * @dataProvider onlyNominatimProvider
	 */
	public final function testOnlyNominatimMocked(?string $expectedAddress, float $lat, float $lon): void
	{
		$coordinates = new Coordinates($lat, $lon);
		$mockedResponsePath = sprintf('%s/fixtures/Nominatim/%s.json', __DIR__, md5($coordinates->getLatLon()));
		$mockedResponseBody = file_get_contents($mockedResponsePath);
		$provider = new UniversalAddressProvider(
			google: null,
			nominatim: $this->createNominatimClientMocked($mockedResponseBody),
		);

		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	/**
	 * @group request
	 * @dataProvider onlyGoogleProvider
	 */
	public final function testOnlyGoogleReal(?string $expectedAddress, float $lat, float $lon): void
	{
		if (!Config::isGooglePlaceApi()) {
			$this->markTestSkipped('Google Place API key is missing.');
		}

		$coordinates = new Coordinates($lat, $lon);
		$provider = new UniversalAddressProvider(
			google: $this->createGoogleClientReal(),
			nominatim: null,
		);
		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	/**
	 * @dataProvider onlyGoogleProvider
	 */
	public final function testOnlyGoogleMocked(?string $expectedAddress, float $lat, float $lon): void
	{
		$coordinates = new Coordinates($lat, $lon);
		$provider = new UniversalAddressProvider(
			google: $this->createGoogleClientOffline(),
			nominatim: null,
		);
		$this->testInner($expectedAddress, $provider, $coordinates);
	}

	private function testInner(?string $expectedAddress, AddressProvider $provider, CoordinatesInterface $coordinates): void
	{
		$address = $provider->reverse($coordinates);

		if ($expectedAddress === null) {
			$this->assertNull($address);
		} else {
			$this->assertInstanceOf(AddressInterface::class, $address);
			$this->assertSame($expectedAddress, $address->getAddress()->toString());
		}
	}

	private function createNominatimClientReal(): NominatimWrapper
	{
		$url = 'https://nominatim.openstreetmap.org';
		$httpClient = new Client([
			'base_uri' => $url,
		]);
		$headers = [
			'User-Agent' => Config::NOMINATIM_USER_AGENT . ' (test)',
		];
		$nominatim = new Nominatim($url, $headers, $httpClient);
		return new NominatimWrapper(TestUtils::createDevNullCache(), $nominatim);
	}

	private function createNominatimClientMocked(string $mockedResponseBody): NominatimWrapper
	{
		$url = 'https://nominatim.openstreetmap.org';
		[$httpClient, $mockHandler] = TestUtils::createMockedHttpClient([
			'base_uri' => $url,
		]);
		assert($httpClient instanceof \GuzzleHttp\Client);
		assert($mockHandler instanceof \GuzzleHttp\Handler\MockHandler);
		$mockHandler->append(new \GuzzleHttp\Psr7\Response(200, body: $mockedResponseBody));

		$nominatim = new Nominatim($url, http_client: $httpClient);
		return new NominatimWrapper(TestUtils::createDevNullCache(), $nominatim);
	}

	private function createGoogleClientReal(): StaticApi
	{
		return new StaticApi(
			(new HttpTestClients())->realRequestor,
			Config::GOOGLE_PLACE_API_KEY,
		);
	}

	private function createGoogleClientOffline(): StaticApi
	{
		return new StaticApi(
			(new HttpTestClients())->offlineRequestor,
			'',
		);
	}
}
