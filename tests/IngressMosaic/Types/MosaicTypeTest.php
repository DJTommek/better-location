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
		$this->assertSame(1788, self::$mosaics[1788]->id);
		$this->assertSame(50.062815, self::$mosaics[1788]->startLat);
		$this->assertSame(14.439725, self::$mosaics[1788]->startLon);

		$this->assertSame(6798, self::$mosaics[6798]->id);
		$this->assertSame(50.087064, self::$mosaics[6798]->startLat);
		$this->assertSame(14.420684, self::$mosaics[6798]->startLon);

		$this->assertSame(7200, self::$mosaics[7200]->id);
		$this->assertSame(50.083698, self::$mosaics[7200]->startLat);
		$this->assertSame(14.433817, self::$mosaics[7200]->startLon);

		$this->assertSame(9966, self::$mosaics[9966]->id);
		$this->assertSame(50.065545, self::$mosaics[9966]->startLat);
		$this->assertSame(14.417146, self::$mosaics[9966]->startLon);

		$this->assertSame(14016, self::$mosaics[14016]->id);
		$this->assertSame(50.08839, self::$mosaics[14016]->startLat);
		$this->assertSame(14.449867, self::$mosaics[14016]->startLon);

		$this->assertSame(25821, self::$mosaics[25821]->id);
		$this->assertSame(50.079798, self::$mosaics[25821]->startLat);
		$this->assertSame(14.429718, self::$mosaics[25821]->startLon);

		$this->assertSame(28470, self::$mosaics[28470]->id);
		$this->assertSame(-45.862487, self::$mosaics[28470]->startLat);
		$this->assertSame(170.515136, self::$mosaics[28470]->startLon);

		$this->assertSame(52183, self::$mosaics[52183]->id);
		$this->assertSame(13.677377, self::$mosaics[52183]->startLat);
		$this->assertSame(-89.283093, self::$mosaics[52183]->startLon);

		$this->assertSame(54594, self::$mosaics[54594]->id);
		$this->assertSame(50.083588, self::$mosaics[54594]->startLat);
		$this->assertSame(14.435511, self::$mosaics[54594]->startLon);
	}

	/** Test general values from left panel */
	public function testActions(): void
	{
		$this->assertSame(44, self::$mosaics[1788]->actions->hacks);
		$this->assertSame(2, self::$mosaics[1788]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[1788]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[1788]->actions->links);
		$this->assertSame(0, self::$mosaics[1788]->actions->fields);
		$this->assertSame(0, self::$mosaics[1788]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[1788]->actions->captures);

		$this->assertSame(462, self::$mosaics[6798]->actions->hacks);
		$this->assertSame(12, self::$mosaics[6798]->actions->waypoints);
		$this->assertSame(72, self::$mosaics[6798]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[6798]->actions->links);
		$this->assertSame(0, self::$mosaics[6798]->actions->fields);
		$this->assertSame(0, self::$mosaics[6798]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[6798]->actions->captures);

		$this->assertSame(202, self::$mosaics[7200]->actions->hacks);
		$this->assertSame(3, self::$mosaics[7200]->actions->waypoints);
		$this->assertSame(24, self::$mosaics[7200]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[7200]->actions->links);
		$this->assertSame(0, self::$mosaics[7200]->actions->fields);
		$this->assertSame(0, self::$mosaics[7200]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[7200]->actions->captures);

		$this->assertSame(440, self::$mosaics[9966]->actions->hacks);
		$this->assertSame(0, self::$mosaics[9966]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[9966]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[9966]->actions->links);
		$this->assertSame(0, self::$mosaics[9966]->actions->fields);
		$this->assertSame(0, self::$mosaics[9966]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[9966]->actions->captures);

		$this->assertSame(20, self::$mosaics[14016]->actions->hacks);
		$this->assertSame(1, self::$mosaics[14016]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[14016]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[14016]->actions->links);
		$this->assertSame(0, self::$mosaics[14016]->actions->fields);
		$this->assertSame(0, self::$mosaics[14016]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[14016]->actions->captures);

		$this->assertSame(169, self::$mosaics[25821]->actions->hacks);
		$this->assertSame(11, self::$mosaics[25821]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[25821]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[25821]->actions->links);
		$this->assertSame(0, self::$mosaics[25821]->actions->fields);
		$this->assertSame(0, self::$mosaics[25821]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[25821]->actions->captures);

		$this->assertSame(72, self::$mosaics[28470]->actions->hacks);
		$this->assertSame(0, self::$mosaics[28470]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[28470]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[28470]->actions->links);
		$this->assertSame(0, self::$mosaics[28470]->actions->fields);
		$this->assertSame(0, self::$mosaics[28470]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[28470]->actions->captures);

		$this->assertSame(77, self::$mosaics[52183]->actions->hacks);
		$this->assertSame(0, self::$mosaics[52183]->actions->waypoints);
		$this->assertSame(1, self::$mosaics[52183]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[52183]->actions->links);
		$this->assertSame(0, self::$mosaics[52183]->actions->fields);
		$this->assertSame(0, self::$mosaics[52183]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[52183]->actions->captures);

		$this->assertSame(60, self::$mosaics[54594]->actions->hacks);
		$this->assertSame(12, self::$mosaics[54594]->actions->waypoints);
		$this->assertSame(0, self::$mosaics[54594]->actions->passhrases);
		$this->assertSame(0, self::$mosaics[54594]->actions->links);
		$this->assertSame(0, self::$mosaics[54594]->actions->fields);
		$this->assertSame(0, self::$mosaics[54594]->actions->deployMods);
		$this->assertSame(0, self::$mosaics[54594]->actions->captures);

	}

	/** Test values from left panel */
	public function testDomValues(): void
	{
		$this->assertSame('0h 7m 16s', self::$mosaics[1788]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('0h 43m 36s', self::$mosaics[1788]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 15m 42s', self::$mosaics[1788]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('1h 34m 0s', self::$mosaics[1788]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(1650, self::$mosaics[1788]->distanceStartEndPortal);
		$this->assertSame(5600, self::$mosaics[1788]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/1/1788.jpg?t=1533815451', self::$mosaics[1788]->image);
		$this->assertSame('2018-08-09', self::$mosaics[1788]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[1788]->locationName);
		$this->assertSame(6, self::$mosaics[1788]->missionsTotal);
		$this->assertSame('Matrix', self::$mosaics[1788]->name);
		$this->assertSame(7.6666666667, self::$mosaics[1788]->portalsAvgPerMission);
		$this->assertSame(46, self::$mosaics[1788]->portalsTotal);
		$this->assertSame(44, self::$mosaics[1788]->portalsUnique);
		$this->assertSame(100, self::$mosaics[1788]->status);
		$this->assertSame('Banner', self::$mosaics[1788]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/1788', self::$mosaics[1788]->url);
		$this->assertTrue(self::$mosaics[1788]->nonstop);

		$this->assertSame('0h 8m 27s', self::$mosaics[6798]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('10h 8m 0s', self::$mosaics[6798]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 9m 30s', self::$mosaics[6798]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('11h 24m 0s', self::$mosaics[6798]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(0, self::$mosaics[6798]->distanceStartEndPortal);
		$this->assertSame(21250, self::$mosaics[6798]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/6/6798.jpg?t=1573977580', self::$mosaics[6798]->image);
		$this->assertSame('2019-11-17', self::$mosaics[6798]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[6798]->locationName);
		$this->assertSame(72, self::$mosaics[6798]->missionsTotal);
		$this->assertSame('Staroměstská radnice s orlojem', self::$mosaics[6798]->name);
		$this->assertSame(7.5833333333, self::$mosaics[6798]->portalsAvgPerMission);
		$this->assertSame(546, self::$mosaics[6798]->portalsTotal);
		$this->assertSame(451, self::$mosaics[6798]->portalsUnique);
		$this->assertSame(100, self::$mosaics[6798]->status);
		$this->assertSame('Banner', self::$mosaics[6798]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/6798', self::$mosaics[6798]->url);
		$this->assertTrue(self::$mosaics[6798]->nonstop);

		$this->assertSame('0h 16m 5s', self::$mosaics[7200]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('6h 26m 0s', self::$mosaics[7200]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 30m 7s', self::$mosaics[7200]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('12h 3m 0s', self::$mosaics[7200]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(13390, self::$mosaics[7200]->distanceStartEndPortal);
		$this->assertSame(156980, self::$mosaics[7200]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/7/7200.jpg?t=1589808873', self::$mosaics[7200]->image);
		$this->assertSame('2020-05-18', self::$mosaics[7200]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[7200]->locationName);
		$this->assertSame(24, self::$mosaics[7200]->missionsTotal);
		$this->assertSame('Pražská nádraží', self::$mosaics[7200]->name);
		$this->assertSame(9.54166666666, self::$mosaics[7200]->portalsAvgPerMission);
		$this->assertSame(229, self::$mosaics[7200]->portalsTotal);
		$this->assertSame(226, self::$mosaics[7200]->portalsUnique);
		$this->assertSame(100, self::$mosaics[7200]->status);
		$this->assertSame('Banner', self::$mosaics[7200]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/7200', self::$mosaics[7200]->url);
		$this->assertNull(self::$mosaics[7200]->nonstop);

		$this->assertSame('0h 3m 31s', self::$mosaics[9966]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('4h 10m 0s', self::$mosaics[9966]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 6m 11s', self::$mosaics[9966]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('7h 19m 0s', self::$mosaics[9966]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(131, self::$mosaics[9966]->distanceStartEndPortal);
		$this->assertSame(24790, self::$mosaics[9966]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/9/9966.jpg?t=1567026716', self::$mosaics[9966]->image);
		$this->assertSame('2019-08-28', self::$mosaics[9966]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[9966]->locationName);
		$this->assertSame(71, self::$mosaics[9966]->missionsTotal);
		$this->assertSame('Poslkádej si Glyphy', self::$mosaics[9966]->name);
		$this->assertSame(6.197183098591, self::$mosaics[9966]->portalsAvgPerMission);
		$this->assertSame(440, self::$mosaics[9966]->portalsTotal);
		$this->assertSame(38, self::$mosaics[9966]->portalsUnique);
		$this->assertSame(100, self::$mosaics[9966]->status);
		$this->assertSame('Banner', self::$mosaics[9966]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/9966', self::$mosaics[9966]->url);
		$this->assertFalse(self::$mosaics[9966]->nonstop);

		$this->assertSame('0h 3m 16s', self::$mosaics[14016]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('0h 9m 48s', self::$mosaics[14016]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 4m 40s', self::$mosaics[14016]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('0h 14m 2s', self::$mosaics[14016]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(33, self::$mosaics[14016]->distanceStartEndPortal);
		$this->assertSame(580, self::$mosaics[14016]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/14/14016.jpg?t=1600625753', self::$mosaics[14016]->image);
		$this->assertSame('2020-09-20', self::$mosaics[14016]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[14016]->locationName);
		$this->assertSame(3, self::$mosaics[14016]->missionsTotal);
		$this->assertSame('Vitkov', self::$mosaics[14016]->name);
		$this->assertSame(7.0, self::$mosaics[14016]->portalsAvgPerMission);
		$this->assertSame(21, self::$mosaics[14016]->portalsTotal);
		$this->assertSame(20, self::$mosaics[14016]->portalsUnique);
		$this->assertSame(100, self::$mosaics[14016]->status);
		$this->assertSame('Banner', self::$mosaics[14016]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/14016', self::$mosaics[14016]->url);
		$this->assertTrue(self::$mosaics[14016]->nonstop);

		$this->assertSame('0h 4m 32s', self::$mosaics[25821]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('2h 16m 0s', self::$mosaics[25821]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 8m 56s', self::$mosaics[25821]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('4h 28m 0s', self::$mosaics[25821]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(623, self::$mosaics[25821]->distanceStartEndPortal);
		$this->assertSame(12110, self::$mosaics[25821]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/25/25821.jpg?t=1574888428', self::$mosaics[25821]->image);
		$this->assertSame('2019-11-27', self::$mosaics[25821]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[25821]->locationName);
		$this->assertSame(30, self::$mosaics[25821]->missionsTotal);
		$this->assertSame('Svatý Václav', self::$mosaics[25821]->name);
		$this->assertSame(6.0, self::$mosaics[25821]->portalsAvgPerMission);
		$this->assertSame(180, self::$mosaics[25821]->portalsTotal);
		$this->assertSame(167, self::$mosaics[25821]->portalsUnique);
		$this->assertSame(100, self::$mosaics[25821]->status);
		$this->assertSame('Banner', self::$mosaics[25821]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/25821', self::$mosaics[25821]->url);
		$this->assertFalse(self::$mosaics[25821]->nonstop);

		$this->assertSame('0h 4m 25s', self::$mosaics[28470]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('0h 53m 0s', self::$mosaics[28470]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 8m 50s', self::$mosaics[28470]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('1h 46m 0s', self::$mosaics[28470]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(0, self::$mosaics[28470]->distanceStartEndPortal);
		$this->assertSame(4980, self::$mosaics[28470]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/28/28470.jpg?t=1589791533', self::$mosaics[28470]->image);
		$this->assertSame('2020-05-18', self::$mosaics[28470]->lastCheck->format('Y-m-d'));
		$this->assertSame('Dunedin', self::$mosaics[28470]->locationName);
		$this->assertSame(12, self::$mosaics[28470]->missionsTotal);
		$this->assertSame('University and Polytechnic Walk', self::$mosaics[28470]->name);
		$this->assertSame(6.0, self::$mosaics[28470]->portalsAvgPerMission);
		$this->assertSame(72, self::$mosaics[28470]->portalsTotal);
		$this->assertSame(70, self::$mosaics[28470]->portalsUnique);
		$this->assertSame(100, self::$mosaics[28470]->status);
		$this->assertSame('Banner', self::$mosaics[28470]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/28470', self::$mosaics[28470]->url);
		$this->assertNull(self::$mosaics[28470]->nonstop);

		$this->assertSame('0h 3m 53s', self::$mosaics[52183]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('0h 46m 43s', self::$mosaics[52183]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 6m 13s', self::$mosaics[52183]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('1h 14m 0s', self::$mosaics[52183]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(0, self::$mosaics[52183]->distanceStartEndPortal);
		$this->assertSame(3290, self::$mosaics[52183]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/52/52183.jpg?t=1575987015', self::$mosaics[52183]->image);
		$this->assertSame('2019-12-10', self::$mosaics[52183]->lastCheck->format('Y-m-d'));
		$this->assertSame('Santa Tecla', self::$mosaics[52183]->locationName);
		$this->assertSame(12, self::$mosaics[52183]->missionsTotal);
		$this->assertSame('El Zodiaco de Santa Tecla', self::$mosaics[52183]->name);
		$this->assertSame(6.5, self::$mosaics[52183]->portalsAvgPerMission);
		$this->assertSame(78, self::$mosaics[52183]->portalsTotal);
		$this->assertSame(74, self::$mosaics[52183]->portalsUnique);
		$this->assertSame(100, self::$mosaics[52183]->status);
		$this->assertSame('Banner', self::$mosaics[52183]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/52183', self::$mosaics[52183]->url);
		$this->assertNull(self::$mosaics[52183]->nonstop);

		$this->assertSame('0h 3m 34s', self::$mosaics[54594]->byBicycleAvg->format('%hh %im %ss'));
		$this->assertSame('0h 42m 55s', self::$mosaics[54594]->byBicycleTotal->format('%hh %im %ss'));
		$this->assertSame('0h 5m 43s', self::$mosaics[54594]->byFootAvg->format('%hh %im %ss'));
		$this->assertSame('1h 8m 0s', self::$mosaics[54594]->byFootTotal->format('%hh %im %ss'));
		$this->assertSame(683, self::$mosaics[54594]->distanceStartEndPortal);
		$this->assertSame(3690, self::$mosaics[54594]->distanceTotal);
		$this->assertSame('https://ingressmosaik.com/image/mosaic/54/54594.jpg?t=1598005947', self::$mosaics[54594]->image);
		$this->assertSame('2020-08-21', self::$mosaics[54594]->lastCheck->format('Y-m-d'));
		$this->assertSame('Prague', self::$mosaics[54594]->locationName);
		$this->assertSame(12, self::$mosaics[54594]->missionsTotal);
		$this->assertSame('OFFLINE Corona Pandemy 2020 - Virus Prevention banner', self::$mosaics[54594]->name);
		$this->assertSame(6.0, self::$mosaics[54594]->portalsAvgPerMission);
		$this->assertSame(72, self::$mosaics[54594]->portalsTotal);
		$this->assertSame(60, self::$mosaics[54594]->portalsUnique);
		$this->assertSame(100, self::$mosaics[54594]->status);
		$this->assertSame('Series', self::$mosaics[54594]->type);
		$this->assertSame('https://ingressmosaic.com/mosaic/54594', self::$mosaics[54594]->url);
		$this->assertTrue(self::$mosaics[54594]->nonstop);
	}
}
