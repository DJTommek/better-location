<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\GeocachingService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';

final class GeocachingServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.087451&lng=14.420671', GeocachingService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.100000&lng=14.500000', GeocachingService::getLink(50.1, 14.5));
		$this->assertEquals('https://www.geocaching.com/play/map?lat=-50.200000&lng=14.600000', GeocachingService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.300000&lng=-14.700001', GeocachingService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://www.geocaching.com/play/map?lat=-50.400000&lng=-14.800008', GeocachingService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		GeocachingService::getLink(50.087451, 14.420671, true);
	}
}
