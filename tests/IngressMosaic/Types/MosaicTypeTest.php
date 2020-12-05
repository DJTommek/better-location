<?php declare(strict_types=1);

use App\IngressMosaic\Types\ActionsType;
use App\IngressMosaic\Types\MosaicType;
use PHPUnit\Framework\TestCase;

final class MosaicTypeTest extends TestCase
{
	/** @var MosaicType[] */
	private static $mosaics = [];

	public static function setUpBeforeClass(): void
	{
		$path = __DIR__ . '/../fixtures/';
		foreach (array_diff(scandir($path), array('.', '..')) as $file) {
			$mosaic = new MosaicType(file_get_contents($path . $file));
			self::$mosaics[$mosaic->id] = $mosaic;
		}
	}

	/**
	 * Group testing - all mosaics have in common
	 */
	public function testCommon(): void
	{
		foreach (self::$mosaics as $mosaicId => $mosaic) {
			$message = sprintf('Invalid mosaic ID %d: "%s"', $mosaicId, $mosaic->name);
			$this->assertInstanceOf(MosaicType::class, $mosaic);
			$this->assertInstanceOf(ActionsType::class, $mosaic->actions, $message);
			$this->assertIsArray($mosaic->attributesRaw, $message);
			$this->assertInstanceOf(DateInterval::class, $mosaic->byBicycleAvg, $message);
			$this->assertInstanceOf(DateInterval::class, $mosaic->byBicycleTotal, $message);
			$this->assertInstanceOf(DateInterval::class, $mosaic->byFootAvg, $message);
			$this->assertInstanceOf(DateInterval::class, $mosaic->byFootTotal, $message);
			$this->assertIsInt($mosaic->distanceStartEndPortal, $message);
			$this->assertGreaterThanOrEqual(0, $mosaic->distanceStartEndPortal);
			$this->assertIsInt($mosaic->distanceTotal, $message);
			$this->assertGreaterThanOrEqual(0, $mosaic->distanceTotal);
			$this->assertIsInt($mosaic->id, $message);
			$this->assertGreaterThanOrEqual(1, $mosaic->id, $message);
			$this->assertIsString($mosaic->image, $message);
			$this->assertInstanceOf(DateTimeImmutable::class, $mosaic->lastCheck, $message);
			$this->assertIsString($mosaic->locationName, $message);
			$this->assertIsInt($mosaic->missionsTotal, $message);
			$this->assertGreaterThanOrEqual(1, $mosaic->missionsTotal, $message);
			$this->assertIsArray($mosaic->mosaicInfoVariableRaw, $message);
			$this->assertIsString($mosaic->name, $message);
			$this->assertIsFloat($mosaic->portalsAvgPerMission, $message);
			$this->assertGreaterThanOrEqual(1, $mosaic->portalsAvgPerMission, $message);
			$this->assertIsInt($mosaic->portalsTotal, $message);
			$this->assertGreaterThanOrEqual(1, $mosaic->portalsTotal, $message);
			$this->assertIsInt($mosaic->portalsUnique, $message);
			$this->assertGreaterThanOrEqual(1, $mosaic->portalsUnique, $message);
			$this->assertIsString($mosaic->responseRaw, $message);
			$this->assertIsFloat($mosaic->startLat, $message);
			$this->assertIsFloat($mosaic->startLon, $message);
			$this->assertIsInt($mosaic->status, $message);
			$this->assertGreaterThanOrEqual(0, $mosaic->status, $message);
			$this->assertIsString($mosaic->type, $message);
			$this->assertIsString($mosaic->url, $message);
		}
	}

	/** Test general values from left panel */
	public function testJsonValues(): void
	{
		$this->assertEquals(1788, self::$mosaics[1788]->id);
		$this->assertEquals(50.062815, self::$mosaics[1788]->startLat);
		$this->assertEquals(14.439725, self::$mosaics[1788]->startLon);
	}

	/** Test values from left panel */
	public function testDomValues(): void
	{
		$this->assertEquals('0h 7m 16s', self::$mosaics[1788]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 43m 36s', self::$mosaics[1788]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 15m 42s', self::$mosaics[1788]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('1h 34m 0s', self::$mosaics[1788]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(1650, self::$mosaics[1788]->distanceStartEndPortal);
		$this->assertEquals(5600, self::$mosaics[1788]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/1/1788.jpg?t=1533815451', self::$mosaics[1788]->image);
		$this->assertEquals('2018-08-09', self::$mosaics[1788]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[1788]->locationName);
		$this->assertEquals(6, self::$mosaics[1788]->missionsTotal);
		$this->assertEquals('Matrix', self::$mosaics[1788]->name);
		$this->assertEquals(7.6666666667, self::$mosaics[1788]->portalsAvgPerMission);
		$this->assertEquals(46, self::$mosaics[1788]->portalsTotal);
		$this->assertEquals(44, self::$mosaics[1788]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[1788]->status);
		$this->assertEquals('Banner', self::$mosaics[1788]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/1788', self::$mosaics[1788]->url);
	}
}
