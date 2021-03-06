<?php declare(strict_types=1);

use App\BetterLocation\Service\OpenStreetMapService;
use PHPUnit\Framework\TestCase;

final class OpenStreetMapServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.openstreetmap.org/search?whereami=1&query=50.087451,14.420671&mlat=50.087451&mlon=14.420671#map=17/50.087451/14.420671', OpenStreetMapService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.openstreetmap.org/search?whereami=1&query=50.100000,14.500000&mlat=50.100000&mlon=14.500000#map=17/50.100000/14.500000', OpenStreetMapService::getLink(50.1, 14.5));
		$this->assertSame('https://www.openstreetmap.org/search?whereami=1&query=-50.200000,14.600000&mlat=-50.200000&mlon=14.600000#map=17/-50.200000/14.600000', OpenStreetMapService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.openstreetmap.org/search?whereami=1&query=50.300000,-14.700001&mlat=50.300000&mlon=-14.700001#map=17/50.300000/-14.700001', OpenStreetMapService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.openstreetmap.org/search?whereami=1&query=-50.400000,-14.800008&mlat=-50.400000&mlon=-14.800008#map=17/-50.400000/-14.800008', OpenStreetMapService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://www.openstreetmap.org/directions?from=&to=50.087451,14.420671', OpenStreetMapService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://www.openstreetmap.org/directions?from=&to=50.100000,14.500000', OpenStreetMapService::getLink(50.1, 14.5, true));
		$this->assertSame('https://www.openstreetmap.org/directions?from=&to=-50.200000,14.600000', OpenStreetMapService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://www.openstreetmap.org/directions?from=&to=50.300000,-14.700001', OpenStreetMapService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://www.openstreetmap.org/directions?from=&to=-50.400000,-14.800008', OpenStreetMapService::getLink(-50.4, -14.800008, true));
	}

	public function testIsValidNormalUrl(): void
	{
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/#map=17/49.355164/14.272819'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/#map=17/49.32085/14.16402&layers=N'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/#map=18/50.05215/14.45283'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/#map=15/-34.6101/-58.3641'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://www.openstreetmap.org/?mlat=-36.9837&mlon=174.8765#map=15/-36.9837/174.8765&layers=N'));
	}

	public function testProcessNormalUrl(): void
	{
		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/#map=17/49.355164/14.272819')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.355164,14.272819', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/#map=17/49.32085/14.16402&layers=N')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.320850,14.164020', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/#map=18/50.05215/14.45283')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.052150,14.452830', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.052150,14.452830', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.053280,14.456400', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/#map=15/-34.6101/-58.3641')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-34.610100,-58.364100', $collection[0]->__toString());

		$collection = OpenStreetMapService::processStatic('https://www.openstreetmap.org/?mlat=-36.9837&mlon=174.8765#map=15/-36.9837/174.8765&layers=N')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-36.983700,174.876500', $collection[0]->__toString());
		$this->assertSame('Point', $collection[0]->getName());
		$this->assertSame('-36.983700,174.876500', $collection[1]->__toString());
		$this->assertSame('Map', $collection[1]->getName());
	}

	public function testIsValidShortUrl(): void
	{
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/0J0kf83sQ--?m='));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/0EEQjE=='));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/0EEQjEEb'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/0J0kf3lAU--'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/0J0kf3lAU--?m='));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/Mnx6vllJ--'));
		$this->assertTrue(OpenStreetMapService::isValidStatic('https://osm.org/go/uuU2nmSl--?layers=N&m='));
	}


	public function testProcessShortUrl(): void
	{
		// https://www.openstreetmap.org/?mlat=50.05296528339386&mlon=14.45624828338623#map=18/50.05296528339386/14.45624828338623
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/0J0kf83sQ--?m=')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.052965,14.456248', $collection[0]->__toString());
		$this->assertSame('Point', $collection[0]->getName());
		$this->assertSame('50.052965,14.456248', $collection[1]->__toString());
		$this->assertSame('Map', $collection[1]->getName());

		// https://www.openstreetmap.org/#map=9/51.510772705078125/0.054931640625
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/0EEQjE==')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('51.510773,0.054932', $collection[0]->__toString());
		$this->assertSame('Map', $collection[0]->getName());

		// https://www.openstreetmap.org/#map=16/51.510998010635376/0.05499601364135742
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/0EEQjEEb')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('51.510998,0.054996', $collection[0]->__toString());
		$this->assertSame('Map', $collection[0]->getName());

		// https://www.openstreetmap.org/#map=18/50.05328983068466/14.454574584960938
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/0J0kf3lAU--')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.053290,14.454575', $collection[0]->__toString());
		$this->assertSame('Map', $collection[0]->getName());

		// https://www.openstreetmap.org/?mlat=50.05328983068466&mlon=14.454574584960938#map=18/50.05328983068466/14.454574584960938
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/0J0kf3lAU--?m=')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('50.053290,14.454575', $collection[0]->__toString());
		$this->assertSame('Point', $collection[0]->getName());
		$this->assertSame('50.053290,14.454575', $collection[1]->__toString());
		$this->assertSame('Map', $collection[1]->getName());

		// https://www.openstreetmap.org/#map=15/-34.61009860038757/-58.36413860321045
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/Mnx6vllJ--')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-34.610099,-58.364139', $collection[0]->__toString());
		$this->assertSame('Map', $collection[0]->getName());

		// https://www.openstreetmap.org/?mlat=-36.98372483253479&mlon=174.87650871276855#map=15/-36.98372483253479/174.87650871276855&layers=N
		$collection = OpenStreetMapService::processStatic('https://osm.org/go/uuU2nmSl--?layers=N&m=')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-36.983725,174.876509', $collection[0]->__toString());
		$this->assertSame('Point', $collection[0]->getName());
		$this->assertSame('-36.983725,174.876509', $collection[1]->__toString());
		$this->assertSame('Map', $collection[1]->getName());
	}
}
