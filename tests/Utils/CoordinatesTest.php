<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;

final class CoordinatesTest extends TestCase
{
	/** @var mixed[] */
	private static $fileFixtures = [];
	/** @var mixed[] */
	private static $jsonFixtures = [];

	public static function setUpBeforeClass(): void
	{
		// parse EXIF files
		$path = __DIR__ . '/fixtures/files/';
		$files = array_diff(scandir($path), array('.', '..'));
		foreach ($files as $file) {
			$exifData = @exif_read_data($path . $file);
			self::assertNotNull($exifData);
			self::$fileFixtures[$file] = $exifData;
		}
		self::assertCount(2, self::$fileFixtures);

		// load pre-parsed EXIF files
		$path = __DIR__ . '/fixtures/json/';
		$files = array_diff(scandir($path), array('.', '..'));
		self::assertCount(6, $files);
		foreach ($files as $file) {
			self::$jsonFixtures[$file] = json_decode(file_get_contents($path . $file), true, 512, JSON_THROW_ON_ERROR);
		}
	}

	public function testExifFiles(): void
	{
		$fixture = self::$fileFixtures['DSCN0010.jpg'];
		$this->assertSame(43.46744833333334, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(11.885126666663888, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$fileFixtures['fujifilm-no-gps.jpg'];
		$this->assertNull($fixture['GPSLatitude']);
		$this->assertNull($fixture['GPSLatitudeRef']);
		$this->assertNull($fixture['GPSLongitude']);
		$this->assertNull($fixture['GPSLongitudeRef']);
	}

	public function testFromString(): void
	{
		$this->assertSame('49.885617,14.044381', Coordinates::fromString('49.885617,14.044381')->key());
		$this->assertSame('-49.885617,14.044381', Coordinates::fromString('-49.885617,14.044381')->key());
		$this->assertSame('49.885617,-14.044381', Coordinates::fromString('49.885617,-14.044381')->key());
		$this->assertSame('-49.885617,-14.044381', Coordinates::fromString('-49.885617,-14.044381')->key());
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567,0.123456')->key());
		$this->assertSame('0.000000,0.000000', Coordinates::fromString('0,0')->key());

		// different separator
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567_0.123456', '_')->key());
		$this->assertNull(Coordinates::fromString('1.234567,0.123456', '_'));

		// multi-character separator separator
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567___0.123456', '___')->key());
		$this->assertNull(Coordinates::fromString('1.234567__0.123456', '___'));
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567_abcd_0.123456', '_abcd_')->key());
		$this->assertNull(Coordinates::fromString('1.234567__0.123456', '___'));
		$this->assertNull(Coordinates::fromString('1.234567___0.123456', '_'));

		$this->assertNull(Coordinates::fromString('some random text'));
		$this->assertNull(Coordinates::fromString('valid coords (49.885617,14.044381) but inside text'));
		$this->assertNull(Coordinates::fromString('95.885617,14.044381')); // lat out of bounds
		$this->assertNull(Coordinates::fromString('1.885617,180.044381')); // lon out of bounds
	}

	public function testExifJson(): void
	{
		$fixture = self::$jsonFixtures['oneplus5t-snezka1.json'];
		$this->assertSame(50.69835122222222, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(15.736727416666666, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$jsonFixtures['oneplus5t-snezka2.json'];
		$this->assertSame(50.69596538888889, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(15.737657194444443, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$jsonFixtures['oneplus5t-snezka3.json'];
		$this->assertSame(50.73308825, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(15.741169194444444, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$jsonFixtures['DSCN0010-local.json'];
		$this->assertSame(43.46744833333334, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(11.885126666663888, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$jsonFixtures['DSCN0010-url.json'];
		$this->assertSame(43.46744833333334, Coordinates::exifToDecimal($fixture['GPSLatitude'], $fixture['GPSLatitudeRef']));
		$this->assertSame(11.885126666663888, Coordinates::exifToDecimal($fixture['GPSLongitude'], $fixture['GPSLongitudeRef']));

		$fixture = self::$jsonFixtures['fujifilm-no-gps.json'];
		$this->assertNull($fixture['GPSLatitude']);
		$this->assertNull($fixture['GPSLatitudeRef']);
		$this->assertNull($fixture['GPSLongitude']);
		$this->assertNull($fixture['GPSLongitudeRef']);
	}

	public function testGpsSubIFDToFloat(): void
	{
		// values from oneplus5t-snezka1
		$this->assertSame(50.0, Coordinates::gpsSubIFDToFloat('50/1'));
		$this->assertSame(41.0, Coordinates::gpsSubIFDToFloat('41/1'));
		$this->assertSame(54.0644, Coordinates::gpsSubIFDToFloat('540644/10000'));
		$this->assertSame(15.0, Coordinates::gpsSubIFDToFloat('15/1'));
		$this->assertSame(44.0, Coordinates::gpsSubIFDToFloat('44/1'));
		$this->assertSame(12.2187, Coordinates::gpsSubIFDToFloat('122187/10000'));

		// values from oneplus5t-snezka2
		$this->assertSame(50.0, Coordinates::gpsSubIFDToFloat('50/1'));
		$this->assertSame(41.0, Coordinates::gpsSubIFDToFloat('41/1'));
		$this->assertSame(45.4754, Coordinates::gpsSubIFDToFloat('454754/10000'));
		$this->assertSame(15.0, Coordinates::gpsSubIFDToFloat('15/1'));
		$this->assertSame(44.0, Coordinates::gpsSubIFDToFloat('44/1'));
		$this->assertSame(15.5659, Coordinates::gpsSubIFDToFloat('155659/10000'));

		// values from DSCN0010
		$this->assertSame(43.0, Coordinates::gpsSubIFDToFloat('43/1'));
		$this->assertSame(28.0, Coordinates::gpsSubIFDToFloat('28/1'));
		$this->assertSame(2.814, Coordinates::gpsSubIFDToFloat('281400000/100000000'));
		$this->assertSame(11.0, Coordinates::gpsSubIFDToFloat('11/1'));
		$this->assertSame(53.0, Coordinates::gpsSubIFDToFloat('53/1'));
		$this->assertSame(6.45599999, Coordinates::gpsSubIFDToFloat('645599999/100000000'));
	}

	public function testWgs84DegreesToDegreesMinutes(): void
	{
		$this->assertSame([50, 5.24706000000009], Coordinates::wgs84DegreesToDegreesMinutes(50.087451));
		$this->assertSame([14, 25.240260000000028], Coordinates::wgs84DegreesToDegreesMinutes(14.420671));
		$this->assertSame([-41, 19.615200000000073], Coordinates::wgs84DegreesToDegreesMinutes(-41.326920));
		$this->assertSame([174, 48.46218000000022], Coordinates::wgs84DegreesToDegreesMinutes(174.807703));
		$this->assertSame([1, 0.0], Coordinates::wgs84DegreesToDegreesMinutes(1));
	}

	public function testWgs84DegreesToDegreesMinutesSeconds(): void
	{
		$this->assertSame([50, 5, 14.8236000000054], Coordinates::wgs84DegreesToDegreesMinutesSeconds(50.087451));
		$this->assertSame([14, 25, 14.41560000000166], Coordinates::wgs84DegreesToDegreesMinutesSeconds(14.420671));
		$this->assertSame([-41, 19, 36.912000000004355], Coordinates::wgs84DegreesToDegreesMinutesSeconds(-41.326920));
		$this->assertSame([174, 48, 27.730800000013005], Coordinates::wgs84DegreesToDegreesMinutesSeconds(174.807703));
		$this->assertSame([1, 0, 0.0], Coordinates::wgs84DegreesToDegreesMinutesSeconds(1));
	}

	public function testDistance(): void
	{
		$this->assertSame(0.0, (new Coordinates(50.087725, 14.4211267))->distance(new Coordinates(50.087725, 14.4211267)));
		$this->assertSame(42.16747601866312, (new Coordinates(50.087725, 14.4211267))->distance(new Coordinates(50.0873667, 14.4213203)));
		$this->assertSame(1_825.0239867033586, (new Coordinates(36.6323425, -121.9340617))->distance(new Coordinates(36.6219297, -121.9182533)));

		$coord1 = new Coordinates(50, 14);
		$coord2 = new Coordinates(51, 15);

		$this->assertEqualsWithDelta( // same coordinates, just switched
			$coord1->distance($coord2),
			$coord2->distance($coord1),
			0.000_000_01
		);
		$this->assertSame(4_532.050463078125, (new Coordinates(50.08904, 14.42890))->distance(new Coordinates(50.07406, 14.48797)));
		$this->assertSame(11_471_646.428581407, (new Coordinates(-50.08904, 14.42890))->distance(new Coordinates(50.07406, -14.48797)));
	}

	/**
	 * Generate random coordinates and compare distance between by using first and second set of method argument.
	 */
	public function testDistanceGenerated(): void
	{
		for ($i = 0; $i < 10_000; $i++) {
			$coords1 = new Coordinates(
				rand(-89_999_999, 89_999_999) / 1_000_000,
				rand(-179_999_999, 179_999_999) / 1_000_000,
			);
			$coords2 = new Coordinates(
				rand(-89_999_999, 89_999_999) / 1_000_000,
				rand(-179_999_999, 179_999_999) / 1_000_000,
			);

			$this->assertEqualsWithDelta(
				$coords1->distance($coords2),
				$coords2->distance($coords1),
				0.000_000_01,
			);
		}
	}

	public function testDistanceStatic(): void
	{
		$this->assertSame(0.0, Coordinates::distanceLatLon(50.087725, 14.4211267, 50.087725, 14.4211267));
		$this->assertSame(42.16747601866312, Coordinates::distanceLatLon(50.087725, 14.4211267, 50.0873667, 14.4213203));
		$this->assertSame(1_825.0239867033586, Coordinates::distanceLatLon(36.6323425, -121.9340617, 36.6219297, -121.9182533));

		$this->assertEqualsWithDelta( // same coordinates, just switched
			Coordinates::distanceLatLon(50, 14, 51, 15),
			Coordinates::distanceLatLon(51, 15, 50, 14),
			0.000_000_01
		);
		$this->assertSame(4_532.050463078125, Coordinates::distanceLatLon(50.08904, 14.42890, 50.07406, 14.48797));
		$this->assertSame(11_471_646.428581407, Coordinates::distanceLatLon(-50.08904, 14.42890, 50.07406, -14.48797));
	}

	/**
	 * Generate random coordinates and compare distance between by using first and second set of method argument.
	 */
	public function testDistanceStaticGenerated(): void
	{
		for ($i = 0; $i < 10_000; $i++) {
			$lat1 = rand(-89_999_999, 89_999_999) / 1_000_000;
			$lon1 = rand(-179_999_999, 179_999_999) / 1_000_000;

			$lat2 = rand(-89_999_999, 89_999_999) / 1_000_000;
			$lon2 = rand(-179_999_999, 179_999_999) / 1_000_000;

			$this->assertEqualsWithDelta(
				Coordinates::distanceLatLon($lat1, $lon1, $lat2, $lon2),
				Coordinates::distanceLatLon($lat2, $lon2, $lat1, $lon1),
				0.000_000_01,
			);
		}
	}
}
