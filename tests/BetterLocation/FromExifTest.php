<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Description;
use App\Icons;
use App\Utils\Formatter;
use PHPUnit\Framework\TestCase;

final class FromExifTest extends TestCase
{
	/**
	 * Images from public image gallery https://github.com/DJTommek/pldr-gallery
	 */
	public static function urlPldrGalleryProvider(): array
	{
		// file as image from https://pldr-gallery.redilap.cz/#/map+from+EXIF/20190811_122938.jpg
		return [
			'Uncopressed (default parameter)' => ['50.695965,15.737657', null, 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn'],
			'Uncompressed (forced)' => ['50.695965,15.737657', null, 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false'],
			'Compressed (forced)' => ['50.695965,15.737657', null, 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=true'],
			'Uncompressed (via download)' => ['50.695965,15.737657', null, 'https://pldr-gallery.redilap.cz/api/download?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn'],
		];
	}

	/** Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/ */
	public static function urlGithubProvider(): array
	{
		return [
			'DSCN0021.jpg' => ['43.467082,11.884538', null, 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg'],
			'DSCN0042.jpg' => ['43.464455,11.881478', null, 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0042.jpg'],
			'No location' => [null, null, 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg'],
		];
	}

	public static function urlInvalidProvider(): array
	{
		return [
			'URL does not exists' => [null, null, 'https://some-invalid.url'],
			'Url exists but it is not image' => [null, null, 'https://tomas.palider.cz/'],
		];
	}

	public static function fileValidProvider(): array
	{
		return [
			'DSCN0010.jpg' => ['43.467448,11.885127', null, __DIR__ . '/../Exif/fixtures/files/DSCN0010.jpg'],
			'gps-including-accuracy-good-local.jpg' => ['28.000428,-82.449653', null, __DIR__ . '/../Exif/fixtures/files/gps-including-accuracy-good-local.jpg'],
			'pixel-with-gps.jpg' => ['50.087451,14.420671', 1234.567, __DIR__ . '/../Exif/fixtures/files/pixel-with-gps.jpg'],
		];
	}

	/**
	 * @group request
	 * @dataProvider urlPldrGalleryProvider
	 * @dataProvider urlGithubProvider
	 * @dataProvider urlInvalidProvider
	 */
	public function testBasicUrl(?string $expectedCoordsKey, ?float $expectedPrecision, string $url): void
	{
		$this->testBasic($expectedCoordsKey, $expectedPrecision, $url);
	}

	/**
	 * @dataProvider fileValidProvider
	 */
	public function testBasicFile(?string $expectedCoordsKey, ?float $expectedPrecision, string $url): void
	{
		$this->testBasic($expectedCoordsKey, $expectedPrecision, $url);
	}

	private function testBasic(?string $expectedCoordsKey, ?float $expectedPrecision, string $input): void
	{
		$location = BetterLocation::fromExif($input);
		if ($expectedCoordsKey === null) {
			$this->assertNull($location);
			return;
		}

		$coords = $location->getCoordinates();
		$this->assertSame($expectedCoordsKey, (string)$coords);

		$descriptionPrecision = $location->getDescription(Description::KEY_PRECISION);
		if ($expectedPrecision === null) {
			$this->assertNull($descriptionPrecision);
			return;
		}

		if ($expectedPrecision) {
			$this->assertSame(
				sprintf('%s Location accuracy is %s.', Icons::WARNING, Formatter::distance($expectedPrecision)),
				$descriptionPrecision->content,
			);
		}
	}

	/**
	 * Example images from https://wikipedia.org/
	 */
	public function testFromURLWikipedia(): void
	{
		$this->markTestSkipped('All files on wikipedia is now returning "content-type: text/html; charset=utf-8" so decoding EXIF is not working.');
		$this->assertSame('50.093652,14.412417', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/5/51/Vltava_river_in_Prague.jpg')->__toString()); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Vltava_in_Prague.jpg
		$this->assertSame('41.888948,-87.624494', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/d/db/GPS_location_stamped_with_GPStamper.jpg')->__toString()); // https://en.wikipedia.org/wiki/Geotagged_photograph#/media/File:GPS_location_stamped_with_GPStamper.jpg

		// no EXIF data
		$this->assertNull(BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Praga_0003.JPG/800px-Praga_0003.JPG')); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Praga_0003.JPG
	}
}
