<?php declare(strict_types=1);

namespace Tests\BetterLocation;

use App\BetterLocation\Description;
use App\BetterLocation\FromExif;
use App\Exif\Exif;
use App\Icons;
use App\Utils\Formatter;
use PHPUnit\Framework\TestCase;

final class FromExifTest extends TestCase
{
	public static function setUpBeforeClass(): void
	{
		if (!Exif::isAvailable()) {
			self::markTestSkipped('Internal library to read EXIF data is not available.');
		}
	}

	/**
	 * Images from public image gallery https://github.com/DJTommek/pldr-gallery
	 */
	public static function urlPldrGalleryProvider(): array
	{
		// file as image from https://pldr-gallery.redilap.cz/#/map+from+EXIF/20190811_122938.jpg
		return [
			'Uncopressed (default parameter)' => ['50.695965,15.737657', null, 'EXIF', 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn'],
			'Uncompressed (forced)' => ['50.695965,15.737657', null, 'EXIF', 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false'],
			'Compressed (forced)' => ['50.695965,15.737657', null, 'EXIF', 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=true'],
			'Uncompressed (via download)' => ['50.695965,15.737657', null, 'EXIF', 'https://pldr-gallery.redilap.cz/api/download?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn'],
		];
	}

	/** Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/ */
	public static function urlGithubProvider(): array
	{
		return [
			'DSCN0021.jpg' => ['43.467082,11.884538', null, 'EXIF', 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg'],
			'DSCN0042.jpg' => ['43.464455,11.881478', null, 'EXIF', 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0042.jpg'],
			'No location' => [null, null, 'EXIF', 'https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg'],
		];
	}

	public static function urlInvalidProvider(): array
	{
		return [
			'URL does not exists' => [null, null, null, 'https://some-invalid.url'],
			'Url exists but it is not image' => [null, null, null, 'https://tomas.palider.cz/'],
		];
	}

	public static function fileValidProvider(): array
	{
		return [
			'DSCN0010.jpg' => ['43.467448,11.885127', null, 'EXIF', __DIR__ . '/../Exif/fixtures/files/DSCN0010.jpg'],
			'gps-including-accuracy-good-local.jpg' => ['28.000428,-82.449653', null, 'EXIF', __DIR__ . '/../Exif/fixtures/files/gps-including-accuracy-good-local.jpg'],
			'pixel-with-gps.jpg' => ['50.087451,14.420671', 1234.567, 'EXIF🛰', __DIR__ . '/../Exif/fixtures/files/pixel-with-gps.jpg'],
			'pixel-with-cellid.jpg' => ['50.087451,14.420671', 1234.567, 'EXIF🗼', __DIR__ . '/../Exif/fixtures/files/pixel-with-cellid.jpg'],
		];
	}

	public static function fileInvalidNotExistsProvider(): array
	{
		return [
			'File does not exists' => [null, null, null, '/file/does/not-exists.jpg'],
		];
	}

	public static function fileInvalidNotImageProvider(): array
	{
		return [
			'File exists but it is not image' => [null, null, null, __FILE__],
		];
	}

	/**
	 * @group request
	 *
	 * @dataProvider urlPldrGalleryProvider
	 * @dataProvider urlGithubProvider
	 * @dataProvider urlInvalidProvider
	 */
	public function testBasicUrl(
		?string $expectedCoordsKey,
		?float $expectedPrecision,
		?string $expectedPrefixMessage,
		string $url,
	): void {
		$this->innerTest($expectedCoordsKey, $expectedPrecision, $expectedPrefixMessage, $url);
	}

	/**
	 * @dataProvider fileValidProvider
	 * @dataProvider fileInvalidNotExistsProvider
	 * @dataProvider fileInvalidNotImageProvider
	 */
	public function testBasicFile(
		?string $expectedCoordsKey,
		?float $expectedPrecision,
		?string $expectedPrefixMessage,
		string $filePath,
	): void {
		$this->innerTest($expectedCoordsKey, $expectedPrecision, $expectedPrefixMessage, $filePath);
	}

	/**
	 * @dataProvider fileValidProvider
	 * @dataProvider fileInvalidNotImageProvider
	 */
	public function testFileContentAsString(
		?string $expectedCoordsKey,
		?float $expectedPrecision,
		?string $expectedPrefixMessage,
		string $filePath,
	): void {
		$fileContent = file_get_contents($filePath);
		$fileContentForExif = 'data://image/jpeg;base64,' . base64_encode($fileContent);
		$this->innerTest($expectedCoordsKey, $expectedPrecision, $expectedPrefixMessage, $fileContentForExif);
	}

	private function innerTest(
		?string $expectedCoordsKey,
		?float $expectedPrecision,
		?string $expectedPrefixMessage,
		string $input,
	): void {
		$fromExif = new FromExif($input);
		$fromExif->run();
		$location = $fromExif->location;

		if ($expectedCoordsKey === null) {
			$this->assertNull($location);
			return;
		}

		$this->assertSame($expectedPrefixMessage, $location->getPrefixMessage());

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

		// https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Vltava_in_Prague.jpg
		$this->assertSame('50.093652,14.412417', (string)(new FromExif('https://upload.wikimedia.org/wikipedia/commons/5/51/Vltava_river_in_Prague.jpg'))->run()->location->getCoordinates());

		// https://en.wikipedia.org/wiki/Geotagged_photograph#/media/File:GPS_location_stamped_with_GPStamper.jpg
		$this->assertSame('41.888948,-87.624494', (string)(new FromExif('https://upload.wikimedia.org/wikipedia/commons/d/db/GPS_location_stamped_with_GPStamper.jpg'))->run()->location->getCoordinates());

		// no EXIF data
		// https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Praga_0003.JPG
		$this->assertNull((string)(new FromExif('https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Praga_0003.JPG/800px-Praga_0003.JPG'))->run()->location);
	}
}
