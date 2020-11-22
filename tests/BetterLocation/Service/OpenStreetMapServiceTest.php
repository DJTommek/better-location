<?php declare(strict_types=1);

use App\BetterLocation\Service\OpenStreetMapService;
use PHPUnit\Framework\TestCase;

final class OpenStreetMapServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671', OpenStreetMapService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://www.openstreetmap.org/search?whereami=1&query=50.100000,14.500000&mlat=50.100000&mlon=14.500000#map=17/50.100000/14.500000', OpenStreetMapService::getLink(50.1, 14.5));
		$this->assertEquals('https://www.openstreetmap.org/search?whereami=1&query=-50.200000,14.600000&mlat=-50.200000&mlon=14.600000#map=17/-50.200000/14.600000', OpenStreetMapService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://www.openstreetmap.org/search?whereami=1&query=50.300000,-14.700001&mlat=50.300000&mlon=-14.700001#map=17/50.300000/-14.700001', OpenStreetMapService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://www.openstreetmap.org/search?whereami=1&query=-50.400000,-14.800008&mlat=-50.400000&mlon=-14.800008#map=17/-50.400000/-14.800008', OpenStreetMapService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertEquals('https://www.openstreetmap.org/directions?from=&to=50.087451,14.420671', OpenStreetMapService::getLink(50.087451, 14.420671, true));
		$this->assertEquals('https://www.openstreetmap.org/directions?from=&to=50.100000,14.500000', OpenStreetMapService::getLink(50.1, 14.5, true));
		$this->assertEquals('https://www.openstreetmap.org/directions?from=&to=-50.200000,14.600000', OpenStreetMapService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertEquals('https://www.openstreetmap.org/directions?from=&to=50.300000,-14.700001', OpenStreetMapService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertEquals('https://www.openstreetmap.org/directions?from=&to=-50.400000,-14.800008', OpenStreetMapService::getLink(-50.4, -14.800008, true));
	}

}
