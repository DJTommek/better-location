<?php declare(strict_types=1);

use App\BetterLocation\Service\OsmAndService;
use PHPUnit\Framework\TestCase;

final class OsmAndServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://osmand.net/go.html?lat=50.087451&lon=14.420671', OsmAndService::getLink(50.087451, 14.420671));
		$this->assertSame('https://osmand.net/go.html?lat=50.100000&lon=14.500000', OsmAndService::getLink(50.1, 14.5));
		$this->assertSame('https://osmand.net/go.html?lat=-50.200000&lon=14.600000', OsmAndService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://osmand.net/go.html?lat=50.300000&lon=-14.700001', OsmAndService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://osmand.net/go.html?lat=-50.400000&lon=-14.800008', OsmAndService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://osmand.net/go.html?lat=50.087451&lon=14.420671', OsmAndService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://osmand.net/go.html?lat=50.100000&lon=14.500000', OsmAndService::getLink(50.1, 14.5, true));
		$this->assertSame('https://osmand.net/go.html?lat=-50.200000&lon=14.600000', OsmAndService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://osmand.net/go.html?lat=50.300000&lon=-14.700001', OsmAndService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://osmand.net/go.html?lat=-50.400000&lon=-14.800008', OsmAndService::getLink(-50.4, -14.800008, true));
	}

	public function testIsUrl(): void
	{
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671&z=17'));
		$this->assertTrue(OsmAndService::isUrl('http://osmand.net/go.html?lat=50.087451&lon=14.420671&z=17'));
		$this->assertTrue(OsmAndService::isUrl('https://OSmAnd.net/go.html?lat=50.087451&lon=14.420671&z=17'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?z=17&lat=50.087451&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&z=17&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go?lat=50.087451&z=17&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://www.osmand.net/go?lat=50.087451&z=17&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('http://osmand.net/go?lat=50.087451&z=17&lon=14.420671'));

		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=-14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=-50.087451&lon=14.420671'));
		$this->assertTrue(OsmAndService::isUrl('https://osmand.net/go.html?lat=-50.087451&lon=-14.420671'));
		// lat or lon out of range
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=91.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=-91.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=220.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=-220.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=51.087451&lon=181.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=51.087451&lon=-181.420671'));

		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lng=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=abc'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=abc&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451aaaa&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671aaaa'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.php?lat=50.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/GO.html?lat=50.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.net/go.HtmL?lat=50.087451&lon=14.420671'));
		$this->assertFalse(OsmAndService::isUrl('https://osmand.org/go.html?lat=50.087451&lon=14.420671'));
	}

	public function testParseUrl()
	{
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671&z=17')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('http://osmand.net/go.html?lat=50.087451&lon=14.420671&z=17')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://OSmAnd.net/go.html?lat=50.087451&lon=14.420671&z=17')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?z=17&lat=50.087451&lon=14.420671')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=50.087451&z=17&lon=14.420671')->__toString());
		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go?lat=50.087451&z=17&lon=14.420671')->__toString());

		$this->assertSame('50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=50.087451&lon=14.420671')->__toString());
		$this->assertSame('50.087451,-14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=50.087451&lon=-14.420671')->__toString());
		$this->assertSame('-50.087451,14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=-50.087451&lon=14.420671')->__toString());
		$this->assertSame('-50.087451,-14.420671', OsmAndService::parseUrl('https://osmand.net/go.html?lat=-50.087451&lon=-14.420671')->__toString());
	}

}
