<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\WazeService;
use Tests\HttpTestClients;

final class WazeServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return WazeService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.waze.com/ul?ll=50.087451,14.420671',
			'https://www.waze.com/ul?ll=50.100000,14.500000',
			'https://www.waze.com/ul?ll=-50.200000,14.600000', // round down
			'https://www.waze.com/ul?ll=50.300000,-14.700001', // round up
			'https://www.waze.com/ul?ll=-50.400000,-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [
			'https://www.waze.com/ul?ll=50.087451,14.420671&navigate=yes',
			'https://www.waze.com/ul?ll=50.100000,14.500000&navigate=yes',
			'https://www.waze.com/ul?ll=-50.200000,14.600000&navigate=yes', // round down
			'https://www.waze.com/ul?ll=50.300000,-14.700001&navigate=yes', // round up
			'https://www.waze.com/ul?ll=-50.400000,-14.800008&navigate=yes',
		];
	}

	public static function isValidNormalUrlProvider(): array
	{
		return [
			[true, 'https://waze.com/ul?ll=50.052098,14.451968'], // https link from @ingressportalbot
			[true, 'https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15'],
			[true, 'https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes'],
			[true, 'https://www.waze.com/ul?ll=50.06300713%2C14.43964005'],
			[true, 'https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website'],
			[true, 'https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016'],
			[true, 'https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842'],
			[true, 'https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842'],
			[true, 'https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363'],
			[true, 'https://www.waze.com/live-map/directions?from=ll.50.093652%2C14.412417'],

			[true, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417'],
			[true, 'https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=14.412417'],
			[true, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=-14.412417'],
			[true, 'https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=-14.412417'],

			[false, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=214.412417'],
			[false, 'https://www.waze.com/livemap/?zoom=11&lat=550.093652&lon=14.412417'],
			[false, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417a'],
			[false, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652a&lon=14.412417'],
			[false, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652a'],
		];
	}

	public static function isValidShortUrlProvider(): array
	{
		return [
			[true, 'https://waze.com/ul/hu2fhzy57j'], // https://www.waze.com/live-map/directions?to=ll.50.087206%2C14.407775
			[true, 'https://waze.com/ul/hu2fk8zezt'], // https://www.waze.com/live-map/directions?to=ll.50.052273%2C14.452407
		];
	}

	public function processNormalUrlProvider(): array
	{
		return [
			[50.052098, 14.451968, 'https://waze.com/ul?ll=50.052098,14.451968'], // https link from @ingressportalbot
			[50.063007, 14.439640, 'https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15'],
			[49.877080, 18.430363, 'https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes'],
			[50.063007, 14.439640, 'https://www.waze.com/ul?ll=50.06300713%2C14.43964005'],
			[50.063007, 14.439640, 'https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website'],
			[50.063007, 14.439640, 'https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016'],
			[50.077344, 14.434758, 'https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842'],
			[50.077344, 14.434758, 'https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842'],
			[49.877080, 18.430363, 'https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363'],
			[50.093652, 14.412417, 'https://www.waze.com/live-map/directions?from=ll.50.093652%2C14.412417'],
			[50.093652, 14.412417, 'https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417'],
			[-50.093652, -14.412417, 'https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=-14.412417'],
		];
	}

	public function processShortUrlProvider(): array
	{
		return [
			[50.087206, 14.407775, 'https://waze.com/ul/hu2fhzy57j'],
			[50.052273, 14.452407, 'https://waze.com/ul/hu2fk8zezt'],
		];
	}

	/**
	 * @dataProvider isValidNormalUrlProvider
	 * @dataProvider isValidShortUrlProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new WazeService($this->httpTestClients->mockedRequestor);
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @dataProvider processNormalUrlProvider
	 */
	public function testProcessNoRequests(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WazeService($this->httpTestClients->mockedRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group request
	 * @dataProvider processShortUrlProvider
	 */
	public function testProcessWithRequestReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WazeService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processShortUrlProvider
	 */
	public function testProcessWithRequestOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WazeService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

}
