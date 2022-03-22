<?php declare(strict_types=1);

use App\BetterLocation\BetterLocation;
use PHPUnit\Framework\TestCase;

/**
 * @group request
 */
final class FromExifTest extends TestCase
{
	/**
	 * Images from public image gallery https://github.com/DJTommek/pldr-gallery
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testFromURLPldrGallery(): void
	{
		// file as image from https://pldr-gallery.redilap.cz/#/map+from+EXIF/20190811_122938.jpg
		// uncopressed (default parameter)
		$this->assertSame('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn')->__toString());
		// uncompressed (forced)
		$this->assertSame('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=false')->__toString());
		// compressed (forced)
		$this->assertSame('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/image?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn&compress=true')->__toString());
		// uncompressed (via download)
		$this->assertSame('50.695965,15.737657', BetterLocation::fromExif('https://pldr-gallery.redilap.cz/api/download?path=JTJGbWFwJTIwZnJvbSUyMEVYSUYlMkYyMDE5MDgxMV8xMjI5MzguanBn')->__toString());
	}

	/** Images from Github repository Exif Samples: https://github.com/ianare/exif-samples/ */
	public function testFromURLGithub(): void
	{
		$this->assertSame('43.467082,11.884538', BetterLocation::fromExif('https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0021.jpg')->__toString());
		$this->assertSame('43.464455,11.881478', BetterLocation::fromExif('https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/gps/DSCN0042.jpg')->__toString());
		// No location available
		$this->assertNull(BetterLocation::fromExif('https://raw.githubusercontent.com/ianare/exif-samples/master/jpg/hdr/canon_hdr_YES.jpg'));
	}

	/**
	 * Example images from https://wikipedia.org/
	 *
	 * @noinspection PhpUnhandledExceptionInspection
	 */
	public function testFromURLWikipedia(): void
	{
		$this->markTestSkipped('All files on wikipedia is now returning "content-type: text/html; charset=utf-8" so decoding EXIF is not working.');
		$this->assertSame('50.093652,14.412417', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/5/51/Vltava_river_in_Prague.jpg')->__toString()); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Vltava_in_Prague.jpg
		$this->assertSame('41.888948,-87.624494', BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/d/db/GPS_location_stamped_with_GPStamper.jpg')->__toString()); // https://en.wikipedia.org/wiki/Geotagged_photograph#/media/File:GPS_location_stamped_with_GPStamper.jpg

		// no EXIF data
		$this->assertNull(BetterLocation::fromExif('https://upload.wikimedia.org/wikipedia/commons/thumb/0/09/Praga_0003.JPG/800px-Praga_0003.JPG')); // https://cs.wikipedia.org/wiki/Praha#/media/Soubor:Praga_0003.JPG
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testFromURLInvalid(): void
	{
		$this->assertNull(BetterLocation::fromExif('https://some-invalid.url'));
	}

}
