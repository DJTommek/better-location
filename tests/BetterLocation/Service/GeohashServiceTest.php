<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\GeohashService;
use PHPUnit\Framework\TestCase;

final class GeohashServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('http://geohash.org/u2fkbnhu9cxe', GeohashService::getLink(50.087451, 14.420671));
		$this->assertSame('http://geohash.org/u2fm1bqtdkzt', GeohashService::getLink(50.1, 14.5));
		$this->assertSame('http://geohash.org/hr46kjr7u9tp', GeohashService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('http://geohash.org/g8vw1kzf9psg', GeohashService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('http://geohash.org/5xj3r0yywz41', GeohashService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		GeohashService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidUrl(): void
	{
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/u2fkbnhu9cxe'));
		$this->assertTrue(GeohashService::isValidStatic('https://geohash.org/u2fkbnhu9cxe'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/6gkzwgjzn820'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/6gkzwgjzn820'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/6gkzmg1w'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/b'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/9'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'));
		$this->assertTrue(GeohashService::isValidStatic('http://geohash.org/c216ne:Mt_Hood')); // with name in url

		$this->assertFalse(GeohashService::isValidStatic('http://geohash.org/'));
		$this->assertFalse(GeohashService::isValidStatic('http://geohash.org/abcdefgh')); // invalid character a
	}

	public function testIsValidCode(): void
	{
		$this->assertTrue(GeohashService::isValidStatic('u2fkbnhu9cxe'));
		$this->assertTrue(GeohashService::isValidStatic('6gkzwgjzn820'));
		$this->assertTrue(GeohashService::isValidStatic('6gkzmg1w'));
		$this->assertTrue(GeohashService::isValidStatic('u'));
		$this->assertTrue(GeohashService::isValidStatic('uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu'));

		$this->assertFalse(GeohashService::isValidStatic('a')); // invalid number of characters
		$this->assertFalse(GeohashService::isValidStatic('uuuuuuuu1uuuuua')); // invalid character, number a
		$this->assertFalse(GeohashService::isValidStatic('c216ne:Mt_Hood')); // do not allow name, it is not part of code but it is ok in URL
	}

	public function testProcessUrl(): void
	{
		$collection = GeohashService::processStatic('http://geohash.org/u2fkbnhu9cxe')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection->getFirst()->__toString());

		// with name
		$collection = GeohashService::processStatic('http://geohash.org/c216ne:Mt_Hood')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('45.370789,-121.701050', $collection->getFirst()->__toString());

		$collection = GeohashService::processStatic('http://geohash.org/6gkzwgjzn820')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.382708,-49.265506', $collection->getFirst()->__toString());

		$collection = GeohashService::processStatic('http://geohash.org/6gkzmg1w')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.426741,-49.315395', $collection->getFirst()->__toString());
	}

	public function testProcessCode(): void
	{
		$collection = GeohashService::processStatic('u2fkbnhu9cxe')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection->getFirst()->__toString());

		$collection = GeohashService::processStatic('6gkzwgjzn820')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.382708,-49.265506', $collection->getFirst()->__toString());

		$collection = GeohashService::processStatic('6gkzmg1w')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-25.426741,-49.315395', $collection->getFirst()->__toString());

	}

	/**
	 * Due to ignoring above certaing precision, these coordinates are same even if geohash is different
	 */
	public function testProcessUrlPrecision(): void
	{
		$coords = '72.580645,40.645161';

		$collection = GeohashService::processStatic('http://geohash.org/uuuuuuuuuuu')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame($coords, $collection->getFirst()->__toString());

		$collection = GeohashService::processStatic('http://geohash.org/uuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuuu')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame($coords, $collection->getFirst()->__toString());

	}

	public function testSearchInText(): void
	{
		$collection = GeohashService::findInText('some random text');
		$this->assertCount(0, $collection); // searching in string is currently disabled, because it is too similar to normal words
	}
}
