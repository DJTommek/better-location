<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class BetterLocationServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://better-location.palider.cz/50.087451,14.420671', BetterLocationService::getLink(50.087451, 14.420671));
		$this->assertSame('https://better-location.palider.cz/50.100000,14.500000', BetterLocationService::getLink(50.1, 14.5));
		$this->assertSame('https://better-location.palider.cz/-50.200000,14.600000', BetterLocationService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://better-location.palider.cz/50.300000,-14.700001', BetterLocationService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://better-location.palider.cz/-50.400000,-14.800008', BetterLocationService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		BetterLocationService::getLink(50.087451, 14.420671, true);
	}

	public function testGenerateCollectionLink(): void
	{
		$collection = new BetterLocationCollection();
		$collection->add(BetterLocation::fromLatLon(50.087451, 14.420671));
		$collection->add(BetterLocation::fromLatLon(50.3, -14.7000009));
		$this->assertSame('https://better-location.palider.cz/50.087451,14.420671;50.300000,-14.700001', BetterLocationService::getShareCollectionLink($collection));
	}

	public function testIsValid(): void
	{
		$this->assertTrue(BetterLocationService::isValidStatic('https://better-location.palider.cz/50.087451,14.420671'));
		$this->assertTrue(BetterLocationService::isValidStatic('https://www.better-location.palider.cz/50.087451,14.420671'));
		$this->assertTrue(BetterLocationService::isValidStatic('http://better-location.palider.cz/50.087451,14.420671'));
		$this->assertTrue(BetterLocationService::isValidStatic('http://www.better-location.palider.cz/50.087451,14.420671'));

		// lat or lon out of range
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/91.087451,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/-91.087451,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/220.087451,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/-220.087451,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/51.087451,181.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/51.087451,-181.420671'));

		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/50.087451&lng=14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/50.087451,abc'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/abc,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/50.087451aaaa,14.420671'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/50.087451,14.420671aaaa'));
		$this->assertFalse(BetterLocationService::isValidStatic('https://better-location.palider.cz/go.php?lat=50.087451,14.420671'));
	}

	public function testParseUrl(): void
	{
		$collection = BetterLocationService::processStatic('https://better-location.palider.cz/50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection[0]->__toString());

		$collection = BetterLocationService::processStatic('https://www.better-location.palider.cz/50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection[0]->__toString());

		$collection = BetterLocationService::processStatic('http://better-location.palider.cz/50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection[0]->__toString());

		$collection = BetterLocationService::processStatic('https://better-location.palider.cz/50.087451,-14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,-14.420671', $collection[0]->__toString());

		$collection = BetterLocationService::processStatic('https://better-location.palider.cz/-50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-50.087451,14.420671', $collection[0]->__toString());

		$collection = BetterLocationService::processStatic('https://better-location.palider.cz/-50.087451,-14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-50.087451,-14.420671', $collection[0]->__toString());
	}

}
