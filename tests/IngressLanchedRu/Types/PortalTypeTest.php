<?php declare(strict_types=1);

use App\IngressLanchedRu\Types\PortalType;
use PHPUnit\Framework\TestCase;

final class PortalTypeTest extends TestCase
{
	private static $getPortalsExample = [];

	private static $portalPrague;

	public static function setUpBeforeClass(): void
	{
		$content = file_get_contents(__DIR__ . '/../fixtures/getPortalsExample.json');
		$json = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		$portals = $json->portalData;
		self::assertCount(62, $portals);
		foreach ($portals as $portal) {
		self::$getPortalsExample[] = PortalType::createFromVariable($portal);
		}

		$content = file_get_contents(__DIR__ . '/../fixtures/portalPrague.json');
		$portals = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::assertCount(1, $portals);
		self::$portalPrague = PortalType::createFromVariable($portals[0]);
	}

	public function testInitial(): void
	{
		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[0]);
		$this->assertEquals('e2e513d0e5e84f5ebd49107e40c64111.16', self::$getPortalsExample[0]->guid);
		$this->assertEquals('Terrain sportif basket', self::$getPortalsExample[0]->name);
		$this->assertEquals(50.042149, self::$getPortalsExample[0]->lat);
		$this->assertEquals(1.412214, self::$getPortalsExample[0]->lng);
		$this->assertNull(self::$getPortalsExample[0]->address);
		$this->assertNull(self::$getPortalsExample[0]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[1]);
		$this->assertEquals('b28e82feac574ad4ad747975eaaeb219.16', self::$getPortalsExample[1]->guid);
		$this->assertEquals('anno JC 1819', self::$getPortalsExample[1]->name);
		$this->assertEquals(50.042174, self::$getPortalsExample[1]->lat);
		$this->assertEquals(1.418436, self::$getPortalsExample[1]->lng);
		$this->assertNull(self::$getPortalsExample[1]->address);
		$this->assertNull(self::$getPortalsExample[1]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[28]);
		$this->assertEquals('d6b1d94d795640fc810a1a052b04b690.16', self::$getPortalsExample[28]->guid);
		$this->assertEquals('Théâtre', self::$getPortalsExample[28]->name); // trimmed
		$this->assertEquals(50.048514, self::$getPortalsExample[28]->lat);
		$this->assertEquals(1.418256, self::$getPortalsExample[28]->lng);
		$this->assertNull(self::$getPortalsExample[28]->address);
		$this->assertNull(self::$getPortalsExample[28]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[39]);
		$this->assertEquals('5278d69739ae40628f9078b41b39e1bd.16', self::$getPortalsExample[39]->guid);
		$this->assertEquals('Glacière Du Château', self::$getPortalsExample[39]->name); // trimmed and UTF-8 characters
		$this->assertEquals(50.048833, self::$getPortalsExample[39]->lat);
		$this->assertEquals(1.415113, self::$getPortalsExample[39]->lng);
		$this->assertNull(self::$getPortalsExample[39]->address);
		$this->assertNull(self::$getPortalsExample[39]->image);


		$this->assertInstanceOf(PortalType::class, self::$portalPrague);
		$this->assertEquals('0bd94fac5de84105b6eef6e7e1639ad9.12', self::$portalPrague->guid);
		$this->assertEquals('Staroměstské náměstí', self::$portalPrague->name);
		$this->assertEquals(50.087451, self::$portalPrague->lat);
		$this->assertEquals(14.420671, self::$portalPrague->lng);
		$this->assertEquals('Old Town Square 1/4, 110 00 Prague-Prague 1, Czech Republic', self::$portalPrague->address);
		$this->assertEquals('https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm', self::$portalPrague->image);
	}

	public function testMethods(): void
	{
		$this->assertEquals('https://intel.ingress.com/?ll=50.042149,1.412214&pll=50.042149,1.412214', self::$getPortalsExample[0]->getIntelLink());
		$this->assertEquals('https://intel.ingress.com/?ll=50.042174,1.418436&pll=50.042174,1.418436', self::$getPortalsExample[1]->getIntelLink());
		$this->assertEquals('https://intel.ingress.com/?ll=50.048514,1.418256&pll=50.048514,1.418256', self::$getPortalsExample[28]->getIntelLink());
		$this->assertEquals('https://intel.ingress.com/?ll=50.048833,1.415113&pll=50.048833,1.415113', self::$getPortalsExample[39]->getIntelLink());
		$this->assertEquals('https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671', self::$portalPrague->getIntelLink());
	}
}
