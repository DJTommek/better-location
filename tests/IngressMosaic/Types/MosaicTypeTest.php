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
		}
	}
}
