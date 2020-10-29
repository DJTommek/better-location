<?php declare(strict_types=1);

use App\BetterLocation\BetterLocation;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../src/bootstrap.php';


final class FromExifTest extends TestCase
{
	/**
	 * Images from public image gallery https://github.com/DJTommek/pldr-gallery
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testFromURLPldrGallery(): void {
		// file as image from https://pldr-gallery.redilap.cz/#/map+from+EXIF/20190811_122938.jpg
		$this->assertEquals('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn')->__toString());
		$this->assertEquals('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false')->__toString());

		// @TODO compressed images don't have any EXIF data, see https://github.com/DJTommek/pldr-gallery/issues/69
		$compressedImage = 'https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=true';
//		$this->assertEquals('50.695965,15.737657', BetterLocation::fromExif($compressedImage)->__toString());
		$this->assertNull(BetterLocation::fromExif($compressedImage));

		$this->assertEquals('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/download?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn')->__toString());
	}

	/**
	 * Example images from https://wikipedia.org/
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testFromURLWikipedia(): void {
		$this->assertEquals('50.093652,14.412417', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/5/51/Vltava_river_in_Prague.jpg')->__toString()); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Vltava_in_Prague.jpg
		$this->assertEquals('41.888948,-87.624494', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/d/db/GPS_location_stamped_with_GPStamper.jpg')->__toString()); // https://en.wikipedia.org/wiki/Geotagged_photograph#/media/File:GPS_location_stamped_with_GPStamper.jpg

		// no EXIF data
		$this->assertNull(BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Praga_0003.JPG/800px-Praga_0003.JPG')); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Praga_0003.JPG
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testFromURLInvalid(): void {
		$this->assertNull(BetterLocation::fromExif('https://some-invalid.url'));
	}

}
