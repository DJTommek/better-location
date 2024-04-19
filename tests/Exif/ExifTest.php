<?php declare(strict_types=1);

namespace Tests\Exif;

use App\Exif\Exif;
use App\Exif\ExifException;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;
use PHPUnit\Framework\TestCase;

final class ExifTest extends TestCase
{
	public static function filesValidProvider(): \Generator
	{
		$data = [
			['DSCN0010', new CoordinatesImmutable(43.46744833333334, 11.885126666663888), null],
			['gps-including-accuracy-good-local', new CoordinatesImmutable(28.000427777777777, -82.44965277777779), 4.7388521666142065],
			['fujifilm-no-gps', null, null],
		];

		foreach ($data as $item) {
			$filename = $item[0];
			$expectedCoords = $item[1];
			$expectedCoordsPrecision = $item[2];

			yield $filename => [
				__DIR__ . '/fixtures/json/' . $filename . '.json',
				$expectedCoords,
				$expectedCoordsPrecision,
				__DIR__ . '/fixtures/files/' . $filename . '.jpg',
			];
		}
	}

	public static function invalidProvider(): array
	{
		return [
			['/some/non/existin/path.jpg'],
			['https://some-invalid.url'],
		];
	}

	/**
	 * @group request
	 */
	public static function urlValidProvider(): \Generator
	{
		$data = [
			// Images from public image gallery https://github.com/DJTommek/pldr-gallery
			[
				'oneplus5t-snezka2',
				new CoordinatesImmutable(50.69596538888889, 15.737657194444443),
				null,
				'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false',
			],
			[
				'DSCN0021',
				new CoordinatesImmutable(43.467081666663894, 11.884538333330555 ),
				null,
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg',
			],

			// Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/
			[
				null, // Unable to parse as JSON, Malformed UTF-8 characters
				null,
				null,
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg',
			],

			// Images from Github repository based on article: https://auth0.com/blog/read-edit-exif-metadata-in-photos-with-javascript/
			[
				'gps-including-accuracy-good-url',
				new CoordinatesImmutable(28.000427777777777, -82.44965277777779),
				4.7388521666142065,
				'https://github.com/auth0-blog/piexifjs-article-companion/blob/a2d15b5f0be5cd961e721a9dd5467b1c9ecca9d9/images/palm%20tree%202.jpg?raw=true',
			],
		];

		foreach ($data as $item) {
			$expectedExifDataJson = $item[0];
			$expectedCoords = $item[1];
			$expectedCoordsPrecision = $item[2];
			$url = $item[3];

			yield $item[0] ?? $url => [
				($expectedExifDataJson === null) ? null : __DIR__ . '/fixtures/json/' . $expectedExifDataJson . '.json',
				$expectedCoords,
				$expectedCoordsPrecision,
				$url,
			];
		}
	}

	/**
	 * @dataProvider filesValidProvider
	 * @dataProvider urlValidProvider
	 */
	public function testValid(
		?string $expectedJsonDataPath,
		?CoordinatesInterface $expectedCoordinates,
		?float $expectedCoordinatesPrecision,
		string $inputPath,
	): void {
		$exif = new Exif($inputPath);

		$this->assertNotSame($exif->getAll(), []);

		if ($expectedJsonDataPath !== null) {

			// Ignore FileDateTime which is not real EXIF information but rather information from filesystem.
			$realJson = (object)$exif->getAll();
			$expectedJsonDataRaw = file_get_contents($expectedJsonDataPath);
			$expectedJson = json_decode($expectedJsonDataRaw);

			unset($realJson->FileDateTime);
			unset($expectedJson->FileDateTime);

			$this->assertJsonStringEqualsJsonString(
				json_encode($expectedJson),
				json_encode($realJson),
			);
		}

		$this->assertSame($expectedCoordinatesPrecision, $exif->getCoordinatesPrecision());

		if ($expectedCoordinates === null) {
			$this->assertFalse($exif->hasCoordinates());
			$this->assertNull($exif->getCoordinates());
		} else {
			$this->assertTrue($exif->hasCoordinates());
			$coords = $exif->getCoordinates();
			$this->assertSame($expectedCoordinates->getLat(), $coords->getLat());
			$this->assertSame($expectedCoordinates->getLon(), $coords->getLon());
		}
	}

	/**
	 * @dataProvider invalidProvider
	 */
	public function testInvalid(
		string $invalidInput,
	): void {
		$this->expectException(ExifException::class);
		$exif = new Exif($invalidInput);
	}
}
