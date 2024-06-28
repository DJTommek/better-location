<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FoursquareService;
use App\Config;
use Tests\HttpTestClients;

final class FoursquareServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return FoursquareService::class;
	}

	protected function getShareLinks(): array
	{
		$this->revalidateGeneratedShareLink = false;
		return [
			'https://foursquare.com/explore?ll=50.087451,14.420671',
			'https://foursquare.com/explore?ll=50.100000,14.500000',
			'https://foursquare.com/explore?ll=-50.200000,14.600000', // round down
			'https://foursquare.com/explore?ll=50.300000,-14.700001', // round up
			'https://foursquare.com/explore?ll=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			// geocaching.com geocache
			[true, 'https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66'],
			[true, 'https://foursquare.com/v/%EC%88%9C%EC%B2%9C%EB%A7%8C-%EA%B0%88%EB%8C%80%EB%B0%AD/4bdd313f4ffaa59381056ff7'],

			[false, 'https://tomas.palider.cz'],
			[false, 'not url'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				50.062101,
				14.442558,
				'<a href="https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66">Foursquare</a> <a href="https://typika.coffee">Typika</a>',
				'<a href="https://facebook.com/typika.specialty.coffee">Facebook</a>, <a href="https://instagram.com/typika.coffee">Instagram</a>, +420 702 031 041',
				'https://foursquare.com/v/typika/5bfe5f9e54b7a90025543a66',
			],
			[
				34.88568980360366,
				127.50945459380536,
				'<a href="https://foursquare.com/v/%EC%88%9C%EC%B2%9C%EB%A7%8C-%EA%B0%88%EB%8C%80%EB%B0%AD/4bdd313f4ffaa59381056ff7">Foursquare</a> <a href="">순천만 갈대밭</a>',
				'+82 61-749-3006',
				'https://foursquare.com/v/%EC%88%9C%EC%B2%9C%EB%A7%8C-%EA%B0%88%EB%8C%80%EB%B0%AD/4bdd313f4ffaa59381056ff7',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new FoursquareService($this->createFoursquareClientMocked());
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessReal(
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		string $expectedDescription,
		string $input
	): void
	{
		$service = new FoursquareService($this->createFoursquareClientReal());
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescription, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		string $expectedDescription,
		string $input
	): void
	{
		$service = new FoursquareService($this->createFoursquareClientOffline());
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescription, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	private function testProcess(
		FoursquareService $service,
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		string $expectedDescription,
		string $input
	): void
	{
		$location = $this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$this->assertSame($expectedPrefix, $location->getPrefixMessage());

		$descriptions = $location->getDescriptions();
		$this->assertCount(1, $descriptions);
		$this->assertSame($expectedDescription, $descriptions[0]->content);
	}

	private function skipIfFoursquareNotSetup(): void
	{
		if (!Config::isFoursquare()) {
			$this->markTestSkipped('Foursquare service is not properly configured.');
		}
	}

	private function createFoursquareClientReal(): \App\Foursquare\Client
	{
		$this->skipIfFoursquareNotSetup();
		// @phpstan-ignore-next-line API credentials might be null, in that case tests are skipped
		return new \App\Foursquare\Client($this->httpTestClients->realRequestor, Config::FOURSQUARE_CLIENT_ID, Config::FOURSQUARE_CLIENT_SECRET);
	}

	private function createFoursquareClientMocked(): \App\Foursquare\Client
	{
		return new \App\Foursquare\Client($this->httpTestClients->mockedRequestor, '', '');
	}

	private function createFoursquareClientOffline(): \App\Foursquare\Client
	{
		return new \App\Foursquare\Client($this->httpTestClients->offlineRequestor, '', '');
	}
}
