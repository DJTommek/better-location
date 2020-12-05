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

		$this->assertEquals(6798, self::$mosaics[6798]->id);
		$this->assertEquals(50.087064, self::$mosaics[6798]->startLat);
		$this->assertEquals(14.420684, self::$mosaics[6798]->startLon);

		$this->assertEquals(7200, self::$mosaics[7200]->id);
		$this->assertEquals(50.083698, self::$mosaics[7200]->startLat);
		$this->assertEquals(14.433817, self::$mosaics[7200]->startLon);

		$this->assertEquals(9966, self::$mosaics[9966]->id);
		$this->assertEquals(50.065545, self::$mosaics[9966]->startLat);
		$this->assertEquals(14.417146, self::$mosaics[9966]->startLon);

		$this->assertEquals(14016, self::$mosaics[14016]->id);
		$this->assertEquals(50.08839, self::$mosaics[14016]->startLat);
		$this->assertEquals(14.449867, self::$mosaics[14016]->startLon);

		$this->assertEquals(25821, self::$mosaics[25821]->id);
		$this->assertEquals(50.079798, self::$mosaics[25821]->startLat);
		$this->assertEquals(14.429718, self::$mosaics[25821]->startLon);

		$this->assertEquals(28470, self::$mosaics[28470]->id);
		$this->assertEquals(-45.862487, self::$mosaics[28470]->startLat);
		$this->assertEquals(170.515136, self::$mosaics[28470]->startLon);

		$this->assertEquals(52183, self::$mosaics[52183]->id);
		$this->assertEquals(13.677377, self::$mosaics[52183]->startLat);
		$this->assertEquals(-89.283093, self::$mosaics[52183]->startLon);

		$this->assertEquals(54594, self::$mosaics[54594]->id);
		$this->assertEquals(50.083588, self::$mosaics[54594]->startLat);
		$this->assertEquals(14.435511, self::$mosaics[54594]->startLon);
	}

	/** Test general values from left panel */
	public function testActions(): void
	{
		$this->assertEquals(44, self::$mosaics[1788]->actions->hacks);
		$this->assertEquals(2, self::$mosaics[1788]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[1788]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[1788]->actions->links);
		$this->assertEquals(0, self::$mosaics[1788]->actions->fields);
		$this->assertEquals(0, self::$mosaics[1788]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[1788]->actions->captures);

		$this->assertEquals(462, self::$mosaics[6798]->actions->hacks);
		$this->assertEquals(12, self::$mosaics[6798]->actions->waypoints);
		$this->assertEquals(72, self::$mosaics[6798]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[6798]->actions->links);
		$this->assertEquals(0, self::$mosaics[6798]->actions->fields);
		$this->assertEquals(0, self::$mosaics[6798]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[6798]->actions->captures);

		$this->assertEquals(202, self::$mosaics[7200]->actions->hacks);
		$this->assertEquals(3, self::$mosaics[7200]->actions->waypoints);
		$this->assertEquals(24, self::$mosaics[7200]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[7200]->actions->links);
		$this->assertEquals(0, self::$mosaics[7200]->actions->fields);
		$this->assertEquals(0, self::$mosaics[7200]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[7200]->actions->captures);

		$this->assertEquals(440, self::$mosaics[9966]->actions->hacks);
		$this->assertEquals(0, self::$mosaics[9966]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[9966]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[9966]->actions->links);
		$this->assertEquals(0, self::$mosaics[9966]->actions->fields);
		$this->assertEquals(0, self::$mosaics[9966]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[9966]->actions->captures);

		$this->assertEquals(20, self::$mosaics[14016]->actions->hacks);
		$this->assertEquals(1, self::$mosaics[14016]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[14016]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[14016]->actions->links);
		$this->assertEquals(0, self::$mosaics[14016]->actions->fields);
		$this->assertEquals(0, self::$mosaics[14016]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[14016]->actions->captures);

		$this->assertEquals(169, self::$mosaics[25821]->actions->hacks);
		$this->assertEquals(11, self::$mosaics[25821]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[25821]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[25821]->actions->links);
		$this->assertEquals(0, self::$mosaics[25821]->actions->fields);
		$this->assertEquals(0, self::$mosaics[25821]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[25821]->actions->captures);

		$this->assertEquals(72, self::$mosaics[28470]->actions->hacks);
		$this->assertEquals(0, self::$mosaics[28470]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[28470]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[28470]->actions->links);
		$this->assertEquals(0, self::$mosaics[28470]->actions->fields);
		$this->assertEquals(0, self::$mosaics[28470]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[28470]->actions->captures);

		$this->assertEquals(77, self::$mosaics[52183]->actions->hacks);
		$this->assertEquals(0, self::$mosaics[52183]->actions->waypoints);
		$this->assertEquals(1, self::$mosaics[52183]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[52183]->actions->links);
		$this->assertEquals(0, self::$mosaics[52183]->actions->fields);
		$this->assertEquals(0, self::$mosaics[52183]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[52183]->actions->captures);

		$this->assertEquals(60, self::$mosaics[54594]->actions->hacks);
		$this->assertEquals(12, self::$mosaics[54594]->actions->waypoints);
		$this->assertEquals(0, self::$mosaics[54594]->actions->passhrases);
		$this->assertEquals(0, self::$mosaics[54594]->actions->links);
		$this->assertEquals(0, self::$mosaics[54594]->actions->fields);
		$this->assertEquals(0, self::$mosaics[54594]->actions->deployMods);
		$this->assertEquals(0, self::$mosaics[54594]->actions->captures);

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
		$this->assertTrue(self::$mosaics[1788]->nonstop);

		$this->assertEquals('0h 8m 27s', self::$mosaics[6798]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('10h 8m 0s', self::$mosaics[6798]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 9m 30s', self::$mosaics[6798]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('11h 24m 0s', self::$mosaics[6798]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(0, self::$mosaics[6798]->distanceStartEndPortal);
		$this->assertEquals(21250, self::$mosaics[6798]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/6/6798.jpg?t=1573977580', self::$mosaics[6798]->image);
		$this->assertEquals('2019-11-17', self::$mosaics[6798]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[6798]->locationName);
		$this->assertEquals(72, self::$mosaics[6798]->missionsTotal);
		$this->assertEquals('Staroměstská radnice s orlojem', self::$mosaics[6798]->name);
		$this->assertEquals(7.5833333333, self::$mosaics[6798]->portalsAvgPerMission);
		$this->assertEquals(546, self::$mosaics[6798]->portalsTotal);
		$this->assertEquals(451, self::$mosaics[6798]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[6798]->status);
		$this->assertEquals('Banner', self::$mosaics[6798]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/6798', self::$mosaics[6798]->url);
		$this->assertTrue(self::$mosaics[6798]->nonstop);

		$this->assertEquals('0h 16m 5s', self::$mosaics[7200]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('6h 26m 0s', self::$mosaics[7200]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 30m 7s', self::$mosaics[7200]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('12h 3m 0s', self::$mosaics[7200]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(13390, self::$mosaics[7200]->distanceStartEndPortal);
		$this->assertEquals(156980, self::$mosaics[7200]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/7/7200.jpg?t=1589808873', self::$mosaics[7200]->image);
		$this->assertEquals('2020-05-18', self::$mosaics[7200]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[7200]->locationName);
		$this->assertEquals(24, self::$mosaics[7200]->missionsTotal);
		$this->assertEquals('Pražská nádraží', self::$mosaics[7200]->name);
		$this->assertEquals(9.54166666666, self::$mosaics[7200]->portalsAvgPerMission);
		$this->assertEquals(229, self::$mosaics[7200]->portalsTotal);
		$this->assertEquals(226, self::$mosaics[7200]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[7200]->status);
		$this->assertEquals('Banner', self::$mosaics[7200]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/7200', self::$mosaics[7200]->url);
		$this->assertNull(self::$mosaics[7200]->nonstop);

		$this->assertEquals('0h 3m 31s', self::$mosaics[9966]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('4h 10m 0s', self::$mosaics[9966]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 6m 11s', self::$mosaics[9966]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('7h 19m 0s', self::$mosaics[9966]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(131, self::$mosaics[9966]->distanceStartEndPortal);
		$this->assertEquals(24790, self::$mosaics[9966]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/9/9966.jpg?t=1567026716', self::$mosaics[9966]->image);
		$this->assertEquals('2019-08-28', self::$mosaics[9966]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[9966]->locationName);
		$this->assertEquals(71, self::$mosaics[9966]->missionsTotal);
		$this->assertEquals('Poslkádej si Glyphy', self::$mosaics[9966]->name);
		$this->assertEquals(6.197183098591, self::$mosaics[9966]->portalsAvgPerMission);
		$this->assertEquals(440, self::$mosaics[9966]->portalsTotal);
		$this->assertEquals(38, self::$mosaics[9966]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[9966]->status);
		$this->assertEquals('Banner', self::$mosaics[9966]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/9966', self::$mosaics[9966]->url);
		$this->assertFalse(self::$mosaics[9966]->nonstop);

		$this->assertEquals('0h 3m 16s', self::$mosaics[14016]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 9m 48s', self::$mosaics[14016]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 4m 40s', self::$mosaics[14016]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 14m 2s', self::$mosaics[14016]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(33, self::$mosaics[14016]->distanceStartEndPortal);
		$this->assertEquals(580, self::$mosaics[14016]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/14/14016.jpg?t=1600625753', self::$mosaics[14016]->image);
		$this->assertEquals('2020-09-20', self::$mosaics[14016]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[14016]->locationName);
		$this->assertEquals(3, self::$mosaics[14016]->missionsTotal);
		$this->assertEquals('Vitkov', self::$mosaics[14016]->name);
		$this->assertEquals(7, self::$mosaics[14016]->portalsAvgPerMission);
		$this->assertEquals(21, self::$mosaics[14016]->portalsTotal);
		$this->assertEquals(20, self::$mosaics[14016]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[14016]->status);
		$this->assertEquals('Banner', self::$mosaics[14016]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/14016', self::$mosaics[14016]->url);
		$this->assertTrue(self::$mosaics[14016]->nonstop);

		$this->assertEquals('0h 4m 32s', self::$mosaics[25821]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('2h 16m 0s', self::$mosaics[25821]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 8m 56s', self::$mosaics[25821]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('4h 28m 0s', self::$mosaics[25821]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(623, self::$mosaics[25821]->distanceStartEndPortal);
		$this->assertEquals(12110, self::$mosaics[25821]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/25/25821.jpg?t=1574888428', self::$mosaics[25821]->image);
		$this->assertEquals('2019-11-27', self::$mosaics[25821]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[25821]->locationName);
		$this->assertEquals(30, self::$mosaics[25821]->missionsTotal);
		$this->assertEquals('Svatý Václav', self::$mosaics[25821]->name);
		$this->assertEquals(6, self::$mosaics[25821]->portalsAvgPerMission);
		$this->assertEquals(180, self::$mosaics[25821]->portalsTotal);
		$this->assertEquals(167, self::$mosaics[25821]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[25821]->status);
		$this->assertEquals('Banner', self::$mosaics[25821]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/25821', self::$mosaics[25821]->url);
		$this->assertFalse(self::$mosaics[25821]->nonstop);

		$this->assertEquals('0h 4m 25s', self::$mosaics[28470]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 53m 0s', self::$mosaics[28470]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 8m 50s', self::$mosaics[28470]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('1h 46m 0s', self::$mosaics[28470]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(0, self::$mosaics[28470]->distanceStartEndPortal);
		$this->assertEquals(4980, self::$mosaics[28470]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/28/28470.jpg?t=1589791533', self::$mosaics[28470]->image);
		$this->assertEquals('2020-05-18', self::$mosaics[28470]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Dunedin', self::$mosaics[28470]->locationName);
		$this->assertEquals(12, self::$mosaics[28470]->missionsTotal);
		$this->assertEquals('University and Polytechnic Walk', self::$mosaics[28470]->name);
		$this->assertEquals(6, self::$mosaics[28470]->portalsAvgPerMission);
		$this->assertEquals(72, self::$mosaics[28470]->portalsTotal);
		$this->assertEquals(70, self::$mosaics[28470]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[28470]->status);
		$this->assertEquals('Banner', self::$mosaics[28470]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/28470', self::$mosaics[28470]->url);
		$this->assertNull(self::$mosaics[28470]->nonstop);

		$this->assertEquals('0h 3m 53s', self::$mosaics[52183]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 46m 43s', self::$mosaics[52183]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 6m 13s', self::$mosaics[52183]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('1h 14m 0s', self::$mosaics[52183]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(0, self::$mosaics[52183]->distanceStartEndPortal);
		$this->assertEquals(3290, self::$mosaics[52183]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/52/52183.jpg?t=1575987015', self::$mosaics[52183]->image);
		$this->assertEquals('2019-12-10', self::$mosaics[52183]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Santa Tecla', self::$mosaics[52183]->locationName);
		$this->assertEquals(12, self::$mosaics[52183]->missionsTotal);
		$this->assertEquals('El Zodiaco de Santa Tecla', self::$mosaics[52183]->name);
		$this->assertEquals(6.5, self::$mosaics[52183]->portalsAvgPerMission);
		$this->assertEquals(78, self::$mosaics[52183]->portalsTotal);
		$this->assertEquals(74, self::$mosaics[52183]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[52183]->status);
		$this->assertEquals('Banner', self::$mosaics[52183]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/52183', self::$mosaics[52183]->url);
		$this->assertNull(self::$mosaics[52183]->nonstop);

		$this->assertEquals('0h 3m 34s', self::$mosaics[54594]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertEquals('0h 42m 55s', self::$mosaics[54594]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertEquals('0h 5m 43s', self::$mosaics[54594]->byFootAvg->format('%hh %im %ss'));
		$this->assertEquals('1h 8m 0s', self::$mosaics[54594]->byFootTotal->format('%hh %im %ss'));
		$this->assertEquals(683, self::$mosaics[54594]->distanceStartEndPortal);
		$this->assertEquals(3690, self::$mosaics[54594]->distanceTotal);
		$this->assertEquals('https://ingressmosaik.com/image/mosaic/54/54594.jpg?t=1598005947', self::$mosaics[54594]->image);
		$this->assertEquals('2020-08-21', self::$mosaics[54594]->lastCheck->format('Y-m-d'));
		$this->assertEquals('Prague', self::$mosaics[54594]->locationName);
		$this->assertEquals(12, self::$mosaics[54594]->missionsTotal);
		$this->assertEquals('OFFLINE Corona Pandemy 2020 - Virus Prevention banner', self::$mosaics[54594]->name);
		$this->assertEquals(6, self::$mosaics[54594]->portalsAvgPerMission);
		$this->assertEquals(72, self::$mosaics[54594]->portalsTotal);
		$this->assertEquals(60, self::$mosaics[54594]->portalsUnique);
		$this->assertEquals(100, self::$mosaics[54594]->status);
		$this->assertEquals('Series', self::$mosaics[54594]->type);
		$this->assertEquals('https://ingressmosaic.com/mosaic/54594', self::$mosaics[54594]->url);
		$this->assertTrue(self::$mosaics[54594]->nonstop);
	}
}
