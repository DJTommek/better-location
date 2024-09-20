<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\Address\AddressProvider;
use App\Address\NullAddressProvider;
use App\BetterLocation\BetterLocation;
use App\BetterLocation\ProcessExample;
use App\BetterLocation\Service\WazeService;
use App\Config;
use App\Google\Geocoding\StaticApi;
use PHPUnit\Framework\TestCase;
use Tests\HttpTestClients;
use unreal4u\TelegramAPI\Telegram;

final class ProcessExampleTest extends TestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	public function testNoAddress(): void
	{
		$wazeService = new WazeService($this->httpTestClients->mockedRequestor);
		$processExample = new ProcessExample($wazeService, new NullAddressProvider());
		$location = $processExample->getExampleLocation();
		$this->assertInstanceOf(BetterLocation::class, $location);
		$this->assertSame(ProcessExample::LAT, $processExample->getLat());
		$this->assertSame(ProcessExample::LON, $processExample->getLon());
		$this->assertFalse($location->hasAddress());
		$this->assertNull($location->getAddress());
	}

	/**
	 * @group request
	 */
	public function testAddressReal(): void
	{
		if (Config::isGoogleGeocodingApi() === false) {
			self::markTestSkipped('Missing Google API key');
		}

		$googleGeocodingApi = new StaticApi($this->httpTestClients->realRequestor, Config::GOOGLE_PLACE_API_KEY);
		$this->testAddress($googleGeocodingApi);
	}

	public function testAddressOffline(): void
	{
		$googleGeocodingApi = new StaticApi($this->httpTestClients->offlineRequestor, '');
		$this->testAddress($googleGeocodingApi);
	}

	private function testAddress(AddressProvider $addressProvider): void
	{
		$wazeService = new WazeService($this->httpTestClients->mockedRequestor);
		$processExample = new ProcessExample($wazeService, $addressProvider);
		$location = $processExample->getExampleLocation();
		$this->assertInstanceOf(BetterLocation::class, $location);
		$this->assertSame(ProcessExample::LAT, $processExample->getLat());
		$this->assertSame(ProcessExample::LON, $processExample->getLon());
		$this->assertTrue($location->hasAddress());
		$this->assertSame('🇨🇿 Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia', $location->getAddress());
	}
}
