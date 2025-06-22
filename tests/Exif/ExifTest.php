<?php declare(strict_types=1);

namespace Tests\Exif;

use App\Exif\Exif;
use App\Exif\ExifException;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;
use PHPUnit\Framework\TestCase;

final class ExifTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!Exif::isAvailable()) {
			self::markTestSkipped('Internal library to read EXIF data is not available.');
			self::markAsRisky();
		}
	}

	public static function validFilesProvider(): \Generator
	{
		$data = [
			['DSCN0010', new CoordinatesImmutable(43.46744833333334, 11.885126666663888), null, null],
			['gps-including-accuracy-good-local', new CoordinatesImmutable(28.000427777777777, -82.44965277777779), 4.7388521666142065, null],
			['fujifilm-no-gps', null, null, null],
			['pixel-with-gps', new CoordinatesImmutable(50.087451, 14.420670999999999), 1234.5670103092784, 'GPS'],
			['pixel-with-cellid', new CoordinatesImmutable(50.087451, 14.420670999999999), 1234.5670103092784, 'CELLID'],
			// First 100 KB of regular JPG file with coordinates in EXIF
			['partial-file', new CoordinatesImmutable(49.49551419997072,  18.25647), null, null],
		];

		foreach ($data as $item) {
			$filename = $item[0];
			$expectedCoords = $item[1];
			$expectedCoordsPrecision = $item[2];
			$expectedGpsProcessingMethod = $item[3];

			yield $filename => [
				__DIR__ . '/fixtures/json/' . $filename . '.json',
				$expectedCoords,
				$expectedCoordsPrecision,
				$expectedGpsProcessingMethod,
				__DIR__ . '/fixtures/files/' . $filename . '.jpg',
			];
		}
	}

	public static function invalidFilesProvider(): array
	{
		return [
			['/some/non/existin/path.jpg'],
		];
	}

	public static function validUrlsProvider(): \Generator
	{
		$data = [
			// Images from public image gallery https://github.com/DJTommek/pldr-gallery
			[
				'oneplus5t-snezka2',
				new CoordinatesImmutable(50.69596538888889, 15.737657194444443),
				null,
				null,
				'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false',
			],
			[
				'DSCN0021',
				new CoordinatesImmutable(43.467081666663894, 11.884538333330555),
				null,
				null,
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg',
			],

			// Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/
			[
				null, // Unable to parse as JSON, Malformed UTF-8 characters
				null,
				null,
				null,
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg',
			],

			// Images from Github repository based on article: https://auth0.com/blog/read-edit-exif-metadata-in-photos-with-javascript/
			[
				'gps-including-accuracy-good-url',
				new CoordinatesImmutable(28.000427777777777, -82.44965277777779),
				4.7388521666142065,
				null,
				'https://github.com/auth0-blog/piexifjs-article-companion/blob/a2d15b5f0be5cd961e721a9dd5467b1c9ecca9d9/images/palm%20tree%202.jpg?raw=true',
			],
		];

		foreach ($data as $item) {
			$expectedExifDataJson = $item[0];
			$expectedCoords = $item[1];
			$expectedCoordsPrecision = $item[2];
			$expectedGpsProcessingMethod = $item[3];
			$url = $item[4];

			yield $item[0] ?? $url => [
				($expectedExifDataJson === null) ? null : __DIR__ . '/fixtures/json/' . $expectedExifDataJson . '.json',
				$expectedCoords,
				$expectedCoordsPrecision,
				$expectedGpsProcessingMethod,
				$url,
			];
		}
	}

	public static function invalidUrlsProvider(): array
	{
		return [
			'URL does not exists' => ['https://some-invalid.url'],
			'Url exists but it is not image' => ['https://tomas.palider.cz/'],
		];
	}

	/**
	 * @group request
	 * @dataProvider validUrlsProvider
	 */
	public function testValidUrl(
		?string $expectedJsonDataPath,
		?CoordinatesInterface $expectedCoordinates,
		?float $expectedCoordinatesPrecision,
		?string $expectedGpsProcessingMethod,
		string $inputPath,
	): void {
		$this->innerTestValid($expectedJsonDataPath, $expectedCoordinates, $expectedCoordinatesPrecision, $expectedGpsProcessingMethod, $inputPath);
	}

	/**
	 * @dataProvider validFilesProvider
	 */
	public function testValidFile(
		?string $expectedJsonDataPath,
		?CoordinatesInterface $expectedCoordinates,
		?float $expectedCoordinatesPrecision,
		?string $expectedGpsProcessingMethod,
		string $inputPath,
	): void {
		$this->innerTestValid($expectedJsonDataPath, $expectedCoordinates, $expectedCoordinatesPrecision, $expectedGpsProcessingMethod, $inputPath);
	}

	/**
	 * Read EXIF data from file as binary string.
	 *
	 * @author https://stackoverflow.com/a/5465741/3334403
	 * @dataProvider validFilesProvider
	 */
	public function testValidFileContentAsString(
		?string $expectedJsonDataPath,
		?CoordinatesInterface $expectedCoordinates,
		?float $expectedCoordinatesPrecision,
		?string $expectedGpsProcessingMethod,
		string $inputPath,
	): void {
		$fileContent = file_get_contents($inputPath);
		$fileContentForExif = 'data://image/jpeg;base64,' . base64_encode($fileContent);
		$this->innerTestValid($expectedJsonDataPath, $expectedCoordinates, $expectedCoordinatesPrecision, $expectedGpsProcessingMethod, $fileContentForExif);
	}

	private function innerTestValid(
		?string $expectedJsonDataPath,
		?CoordinatesInterface $expectedCoordinates,
		?float $expectedCoordinatesPrecision,
		?string $expectedGpsProcessingMethod,
		string $inputPath,
	): void {
		$exif = new Exif($inputPath);

		$this->assertNotSame([], $exif->getAll());

		if ($expectedJsonDataPath !== null) {

			// Ignore FileName and FileDateTime which is not real EXIF information but rather information from filesystem.
			$realJson = (object)$exif->getAll();
			$expectedJsonDataRaw = file_get_contents($expectedJsonDataPath);
			$expectedJson = json_decode($expectedJsonDataRaw);

			unset($realJson->FileName);
			unset($expectedJson->FileName);
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
			$this->assertNull($exif->getGpsProcessingMethod());
		} else {
			$this->assertTrue($exif->hasCoordinates());
			$coords = $exif->getCoordinates();
			$this->assertSame($expectedCoordinates->getLat(), $coords->getLat());
			$this->assertSame($expectedCoordinates->getLon(), $coords->getLon());
			$this->assertSame($expectedGpsProcessingMethod, $exif->getGpsProcessingMethod());
		}
	}

	/**
	 * @group request
	 * @dataProvider invalidUrlsProvider
	 */
	public function testInvalidUrls(string $invalidInput): void
	{
		$this->expectException(ExifException::class);
		new Exif($invalidInput);
	}

	/**
	 * @dataProvider invalidFilesProvider
	 */
	public function testInvalidFiles(string $invalidInput): void
	{
		$this->expectException(ExifException::class);
		new Exif($invalidInput);
	}
}
