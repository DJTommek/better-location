<?php declare(strict_types=1);

namespace Tests\IngressLanchedRu\Types;

use App\IngressLanchedRu\Types\PortalType;
use PHPUnit\Framework\TestCase;

final class PortalTypeTest extends TestCase
{
	/** @var PortalType[] */
	private static array $getPortalsExample = [];

	private static PortalType $portalPrague;
	private static PortalType $portalNameAsInt;

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

		$content = file_get_contents(__DIR__ . '/../fixtures/portalNameAsInt.json');
		$portals = json_decode($content, false, 512, JSON_THROW_ON_ERROR);
		self::assertCount(1, $portals);
		self::$portalNameAsInt = PortalType::createFromVariable($portals[0]);
	}

	public function testInitial(): void
	{
		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[0]);
		$this->assertSame('e2e513d0e5e84f5ebd49107e40c64111.16', self::$getPortalsExample[0]->guid);
		$this->assertSame('Terrain sportif basket', self::$getPortalsExample[0]->name);
		$this->assertSame(50.042149, self::$getPortalsExample[0]->lat);
		$this->assertSame(1.412214, self::$getPortalsExample[0]->lng);
		$this->assertNull(self::$getPortalsExample[0]->address);
		$this->assertNull(self::$getPortalsExample[0]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[1]);
		$this->assertSame('b28e82feac574ad4ad747975eaaeb219.16', self::$getPortalsExample[1]->guid);
		$this->assertSame('anno JC 1819', self::$getPortalsExample[1]->name);
		$this->assertSame(50.042174, self::$getPortalsExample[1]->lat);
		$this->assertSame(1.418436, self::$getPortalsExample[1]->lng);
		$this->assertNull(self::$getPortalsExample[1]->address);
		$this->assertNull(self::$getPortalsExample[1]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[28]);
		$this->assertSame('d6b1d94d795640fc810a1a052b04b690.16', self::$getPortalsExample[28]->guid);
		$this->assertSame('Théâtre', self::$getPortalsExample[28]->name); // trimmed
		$this->assertSame(50.048514, self::$getPortalsExample[28]->lat);
		$this->assertSame(1.418256, self::$getPortalsExample[28]->lng);
		$this->assertNull(self::$getPortalsExample[28]->address);
		$this->assertNull(self::$getPortalsExample[28]->image);

		$this->assertInstanceOf(PortalType::class, self::$getPortalsExample[39]);
		$this->assertSame('5278d69739ae40628f9078b41b39e1bd.16', self::$getPortalsExample[39]->guid);
		$this->assertSame('Glacière Du Château', self::$getPortalsExample[39]->name); // trimmed and UTF-8 characters
		$this->assertSame(50.048833, self::$getPortalsExample[39]->lat);
		$this->assertSame(1.415113, self::$getPortalsExample[39]->lng);
		$this->assertNull(self::$getPortalsExample[39]->address);
		$this->assertNull(self::$getPortalsExample[39]->image);
	}

	public function testPortalPrague()
	{
		$this->assertInstanceOf(PortalType::class, self::$portalPrague);
		$this->assertSame('0bd94fac5de84105b6eef6e7e1639ad9.12', self::$portalPrague->guid);
		$this->assertSame('Staroměstské náměstí', self::$portalPrague->name);
		$this->assertSame(50.087451, self::$portalPrague->lat);
		$this->assertSame(14.420671, self::$portalPrague->lng);
		$this->assertSame('Old Town Square 1/4, 110 00 Prague-Prague 1, Czech Republic', self::$portalPrague->address);
		$this->assertSame('https://lh3.googleusercontent.com/8fh0CQtf1xyCw4hbv6-IGauvi3eOyHRmzammie2lG6s591lEesKEcVbkcnZk_fWWlCTuYIdxN7EKJyvq4Nmpi5yBSWmm', self::$portalPrague->image);
	}

	public function testPortalNameAsInt()
	{
		$this->assertInstanceOf(PortalType::class, self::$portalNameAsInt);
		$this->assertSame('470292c1672441d18585709d871f27d7.16', self::$portalNameAsInt->guid);
		$this->assertSame('1737', self::$portalNameAsInt->name); // in JSON it is int
		$this->assertSame(49.456762, self::$portalNameAsInt->lat);
		$this->assertSame(13.784239, self::$portalNameAsInt->lng);
		$this->assertSame('Lnáře 177, 387 42 Lnáře, Czechia', self::$portalNameAsInt->address);
		$this->assertSame('https://lh3.googleusercontent.com/_OKdqcvDYCBJAXpN5_vud7KaQ_7jsmpc1Fm5kBWB7fv-CzWNB63b7eI-QNr2WQ3jEqJUNOeRU4Dtm1TYx5q38NbomQE', self::$portalNameAsInt->image);
	}

	public function testMethods(): void
	{
		$this->assertSame('https://intel.ingress.com/intel?pll=50.042149,1.412214', self::$getPortalsExample[0]->getIntelLink());
		$this->assertSame('https://intel.ingress.com/intel?pll=50.042174,1.418436', self::$getPortalsExample[1]->getIntelLink());
		$this->assertSame('https://intel.ingress.com/intel?pll=50.048514,1.418256', self::$getPortalsExample[28]->getIntelLink());
		$this->assertSame('https://intel.ingress.com/intel?pll=50.048833,1.415113', self::$getPortalsExample[39]->getIntelLink());
		$this->assertSame('https://intel.ingress.com/intel?pll=50.087451,14.420671', self::$portalPrague->getIntelLink());
		$this->assertSame('https://intel.ingress.com/intel?pll=49.456762,13.784239', self::$portalNameAsInt->getIntelLink());
	}
}
