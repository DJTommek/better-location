<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\OpenElevation\OpenElevation;
use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;

final class OpenElevationTest extends TestCase
{
	/** @var OpenElevation */
	private static $api;

	public static function setUpBeforeClass(): void
	{
		self::$api = new OpenElevation();
	}

	public function testLookup(): void
	{
		$result = self::$api->lookup(50.087451, 14.420671);
		$this->assertInstanceOf(Coordinates::class, $result);
		$this->assertSame(50.087451, $result->getLat());
		$this->assertSame(14.420671, $result->getLon());
		$this->assertSame(206.0, $result->getElevation());
	}

	public function testLookupBatch(): void
	{
		$input = [
			[50.087451, 14.420671],
			[-33.4255422, -70.6328236],
		];
		$result = self::$api->lookupBatch($input);
		$this->assertCount(2, $result);
		$this->assertInstanceOf(Coordinates::class, $result[0]);
		$this->assertSame(50.087451, $result[0]->getLat());
		$this->assertSame(14.420671, $result[0]->getLon());
		$this->assertSame(206.0, $result[0]->getElevation());
		$this->assertInstanceOf(Coordinates::class, $result[1]);
		$this->assertSame(-33.4255422, $result[1]->getLat());
		$this->assertSame(-70.6328236, $result[1]->getLon());
		$this->assertSame(823.0, $result[1]->getElevation());
	}

	public function testFill(): void
	{
		$coords = new Coordinates(50.087451, 14.420671);
		$this->assertNull($coords->getElevation());
		self::$api->fill($coords);
		$this->assertSame(206.0, $coords->getElevation());
	}

	public function testLookupInvalidCoords(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		self::$api->lookup(99.087451, 14.420671);
	}

	public function testLookupBatchInvalidCoords(): void
	{
		$input = [
			[99.087451, 14.420671],
			[-33.4255422, -70.6328236],
		];
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		self::$api->lookupBatch($input);
	}

	public function testLookupBatchInvalidArray(): void
	{
		$input = [
			[50.087451, 14.420671],
			[-33.4255422], // missing latitude
		];
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Longitude coordinate must be numeric between or equal from -180 to 180 degrees.');
		self::$api->lookupBatch($input);
	}
}
