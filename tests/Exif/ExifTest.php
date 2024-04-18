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
			['DSCN0010', new CoordinatesImmutable(43.46744833333334, 11.885126666663888)],
			['gps-including-accuracy-good', new CoordinatesImmutable(28.000427777777777, -82.44965277777779)],
			['fujifilm-no-gps', null],
		];

		foreach ($data as $item) {
			$filename = $item[0];
			$expectedCoords = $item[1];

			yield $filename => [
				__DIR__ . '/fixtures/json/' . $filename . '.json',
				$expectedCoords,
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
				'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false',
			],
			[
				'DSCN0021',
				new CoordinatesImmutable(43.467081666663894, 11.884538333330555 ),
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg',
			],

			// Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/
			[
				null, // Unable to parse as JSON, Malformed UTF-8 characters
				null,
				'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg',
			],
		];

		foreach ($data as $item) {
			$expectedExifDataJson = $item[0];
			$expectedCoords = $item[1];
			$url = $item[2];

			yield $item[0] ?? $url => [
				($expectedExifDataJson === null) ? null : __DIR__ . '/fixtures/json/' . $expectedExifDataJson . '.json',
				$expectedCoords,
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
		string $inputPath,
	): void {
		$exif = new Exif($inputPath);

		if ($expectedJsonDataPath !== null) {
			$this->assertJsonStringEqualsJsonFile($expectedJsonDataPath, json_encode($exif));
		}

		$this->assertNotSame($exif->getAll(), []);

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
