<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\BetterLocation\Service\WazeService;

final class WazeServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.waze.com/ul?ll=50.087451,14.420671', WazeService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.waze.com/ul?ll=50.100000,14.500000', WazeService::getLink(50.1, 14.5));
		$this->assertSame('https://www.waze.com/ul?ll=-50.200000,14.600000', WazeService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.waze.com/ul?ll=50.300000,-14.700001', WazeService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.waze.com/ul?ll=-50.400000,-14.800008', WazeService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://www.waze.com/ul?ll=50.087451,14.420671&navigate=yes', WazeService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://www.waze.com/ul?ll=50.100000,14.500000&navigate=yes', WazeService::getLink(50.1, 14.5, true));
		$this->assertSame('https://www.waze.com/ul?ll=-50.200000,14.600000&navigate=yes', WazeService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://www.waze.com/ul?ll=50.300000,-14.700001&navigate=yes', WazeService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://www.waze.com/ul?ll=-50.400000,-14.800008&navigate=yes', WazeService::getLink(-50.4, -14.800008, true));
	}

	public function testIsValidShortUrl(): void
	{
		$this->assertTrue(WazeService::isValidStatic('https://waze.com/ul/hu2fhzy57j')); // https://www.waze.com/live-map/directions?to=ll.50.087206%2C14.407775
		$this->assertTrue(WazeService::isValidStatic('https://waze.com/ul/hu2fk8zezt')); // https://www.waze.com/live-map/directions?to=ll.50.052273%2C14.452407
	}

	public function testProcessShortUrl(): void
	{
		$collection = WazeService::processStatic('https://waze.com/ul/hu2fhzy57j')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087206,14.407775', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://waze.com/ul/hu2fk8zezt')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.052273,14.452407', $collection[0]->__toString());
	}

	public function testIsValidNormalUrl(): void
	{
		$this->assertTrue(WazeService::isValidStatic('https://waze.com/ul?ll=50.052098,14.451968')); // https link from @ingressportalbot
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/ul?ll=50.06300713%2C14.43964005'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363'));

		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=14.412417'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=-14.412417'));
		$this->assertTrue(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=-14.412417'));

		$this->assertFalse(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=214.412417'));
		$this->assertFalse(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=550.093652&lon=14.412417'));
		$this->assertFalse(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417a'));
		$this->assertFalse(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652a&lon=14.412417'));
		$this->assertFalse(WazeService::isValidStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652a'));
	}

	public function testProcessNormalUrl(): void
	{
		$collection = WazeService::processStatic('https://waze.com/ul?ll=50.052098,14.451968')->getCollection(); // https link from @ingressportalbot
		$this->assertCount(1, $collection);
		$this->assertSame('50.052098,14.451968', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.063007,14.439640', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.877080,18.430363', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/ul?ll=50.06300713%2C14.43964005')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.063007,14.439640', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.063007,14.439640',  $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.063007,14.439640', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.077344,14.434758', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.077344,14.434758', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.877080,18.430363', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.093652,14.412417', $collection[0]->__toString());

		$collection = WazeService::processStatic('https://www.waze.com/livemap/?zoom=11&lat=-50.093652&lon=-14.412417')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-50.093652,-14.412417', $collection[0]->__toString());
	}
}
