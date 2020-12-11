<?php declare(strict_types=1);

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
		var_dump($fixture);
		$this->assertNull($fixture['GPSLatitude']);
		$this->assertNull($fixture['GPSLatitudeRef']);
		$this->assertNull($fixture['GPSLongitude']);
		$this->assertNull($fixture['GPSLongitudeRef']);
	}
}
