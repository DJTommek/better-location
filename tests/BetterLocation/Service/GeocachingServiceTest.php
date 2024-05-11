<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\GeocachingService;
use App\Config;
use PHPUnit\Framework\TestCase;

final class GeocachingServiceTest extends TestCase
{
	private function assertProcessSetup(): void
	{
		if (!Config::isGeocaching()) {
			$this->markTestSkipped('Geocaching service is not properly configured.');
		}
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.geocaching.com/play/map?lat=50.087451&lng=14.420671', GeocachingService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.geocaching.com/play/map?lat=50.100000&lng=14.500000', GeocachingService::getLink(50.1, 14.5));
		$this->assertSame('https://www.geocaching.com/play/map?lat=-50.200000&lng=14.600000', GeocachingService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.geocaching.com/play/map?lat=50.300000&lng=-14.700001', GeocachingService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.geocaching.com/play/map?lat=-50.400000&lng=-14.800008', GeocachingService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		GeocachingService::getLink(50.087451, 14.420671, true);
	}

	public function testIsUrl(): void
	{
		// geocaching.com geocache
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('http://www.geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('http://geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://GEOcacHing.cOm/geocache/GC3dyC4'));

		// geocaching.com geocache with name
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722'));

		// geocaching.com geocache guid
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/seek/cache_details.aspx?GUID=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));

		// geocaching.com map geocache
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map/gC3dyC4'));

		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com'));
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/geocache/'));
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/geocache/AA3DYC4'));

		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/seek/cache_details.aspx?guid={498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}'));
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/seek/cache_details.aaa?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/seek/blabla.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertFalse(GeocachingService::isValidStatic('https://coord.info/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));

		// geocaching.com map search
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map/?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=-50.08&lng=14.42115&zoom=18&asc=true&sort=distance'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=-51.705545&lng=-57.933311&zoom=12&asc=true&sort=distance&sw=1'));

		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=-51.aaa&lng=123&zoom=12&asc=true&sort=distance&sw=1')); // invalid lat
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=-51.705545&lng=123aa&zoom=12&asc=true&sort=distance&sw=1')); // invalid lng
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=95&lng=123&zoom=12&asc=true&sort=distance&sw=1')); // lat over limit
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/play/map?lat=49.5&lng=191.111&zoom=12&asc=true&sort=distance&sw=1')); // lng over limit

		// geocaching.com map browse
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=50.05821,14.457&z=16'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=-50.08,14.42115&z=9'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?z=10&ll=-51.705545,-57.933311'));

		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=50.aaa,14.457&z=16')); // invalid lat
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=50.05821,14.123aaa&z=16')); // invalid lng
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=95.05821,14.457&z=16')); // lat over limit
		$this->assertFalse(GeocachingService::isValidStatic('https://www.geocaching.com/map/#?ll=50.05821,194.457&z=16')); // lng over limit

		// coord.info map browse
		$this->assertTrue(GeocachingService::isValidStatic('http://coord.info/map?ll=50.05821,14.457&z=16'));
		$this->assertTrue(GeocachingService::isValidStatic('http://coord.info/map?ll=-50.08,14.42115&z=9'));
		$this->assertTrue(GeocachingService::isValidStatic('http://coord.info/map?z=10&ll=-51.705545,-57.933311'));

		$this->assertFalse(GeocachingService::isValidStatic('http://coord.info/map?ll=50.aaa,14.457&z=16')); // invalid lat
		$this->assertFalse(GeocachingService::isValidStatic('http://coord.info/map?ll=50.05821,14.123aaa&z=16')); // invalid lng
		$this->assertFalse(GeocachingService::isValidStatic('http://coord.info/map?ll=95.05821,14.457&z=16')); // lat over limit
		$this->assertFalse(GeocachingService::isValidStatic('http://coord.info/map?ll=50.05821,194.457&z=16')); // lng over limit

		// coord.info geocache
		$this->assertTrue(GeocachingService::isValidStatic('https://coord.info/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://www.coord.info/GC3DYC4'));
		$this->assertTrue(GeocachingService::isValidStatic('https://coOrD.INfo/Gc3dyC4'));

		$this->assertFalse(GeocachingService::isValidStatic('https://coord.info/AA3dyC4'));
		$this->assertFalse(GeocachingService::isValidStatic('https://coord.info/GC'));
	}


	public function testGetCacheIdFromUrlGeocachingCom(): void
	{
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/geocache/GC3DYC4')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://geocaching.com/geocache/GC3DYC4')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://GEOcacHing.cOm/geocache/GC3dyC4')));
		// including name
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722')));
		// from map
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/play/map/GC3DYC4')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/play/map/gC3dyC4')));

		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/play/map/gc'))); // missing ID after prefix
		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/play/map/BB3DYC4'))); // missing correct prefix
		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/aaaaaaaa/GC3dyC4'))); // wrong path
		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.geocaching.com/geocache/GC3DYC4-find-the-bug'))); // invalid divider before ID and name
	}

	public function testGetGeocacheIdFromUrlCoordInfo(): void
	{
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://coord.info/GC3DYC4')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://www.coord.info/GC3DYC4')));
		$this->assertSame('GC3DYC4', GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://coOrD.INfo/Gc3dyC4')));

		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://coord.info/AA3dyC4')));
		$this->assertNull(GeocachingService::getGeocacheIdFromUrl(new \Nette\Http\Url('https://coord.info/GC')));
	}

	/**
	 * @group request
	 */
	public function testGetCoordsFromMapSearchUrl(): void
	{
		$this->assertProcessSetup();

		$this->assertSame([50.087717, 14.42115], GeocachingService::processStatic('https://www.geocaching.com/play/map?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance')->getFirst()->getLatLon());
		$this->assertSame([50.087717, 14.42115], GeocachingService::processStatic('https://www.geocaching.com/play/map/?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance')->getFirst()->getLatLon());
		$this->assertSame([-50.08, 14.42115], GeocachingService::processStatic('https://www.geocaching.com/play/map?lat=-50.08&lng=14.42115&zoom=18&asc=true&sort=distance')->getFirst()->getLatLon());
		$this->assertSame([-51.705545, -57.933311], GeocachingService::processStatic('https://www.geocaching.com/play/map?lat=-51.705545&lng=-57.933311&zoom=12&asc=true&sort=distance&sw=1')->getFirst()->getLatLon());
	}

	/**
	 * @group request
	 */
	public function testGetCoordsFromMapBrowseUrl(): void
	{
		$this->assertProcessSetup();

		$this->assertSame([50.05821, 14.457], GeocachingService::processStatic('https://www.geocaching.com/map/#?ll=50.05821,14.457&z=16')->getFirst()->getLatLon());
		$this->assertSame([-50.08, 14.42115], GeocachingService::processStatic('https://www.geocaching.com/map/#?ll=-50.08,14.42115&z=9')->getFirst()->getLatLon());
		$this->assertSame([-51.705545, -57.933311], GeocachingService::processStatic('https://www.geocaching.com/map/#?z=10&ll=-51.705545,-57.933311')->getFirst()->getLatLon());
	}

	/**
	 * @group request
	 */
	public function testGetCoordsFromMapCoordInfoUrl(): void
	{
		$this->assertProcessSetup();

		$this->assertSame([50.05821, 14.457], GeocachingService::processStatic('http://coord.info/map?ll=50.05821,14.457&z=16')->getFirst()->getLatLon());
		$this->assertSame([-50.08, 14.42115], GeocachingService::processStatic('http://coord.info/map?ll=-50.08,14.42115&z=9')->getFirst()->getLatLon());
		$this->assertSame([-51.705545, -57.933311], GeocachingService::processStatic('http://coord.info/map?z=10&ll=-51.705545,-57.933311')->getFirst()->getLatLon());
	}

	public function testGetGeocachesIdFromText(): void
	{
		$this->assertSame(['GC1111', 'GC12ABD'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline
gc12aBd, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['GC1111', 'GC12ABD'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline gc12aBd
, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['GC1111', 'GC12ABD'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline 
gc12aBd
, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['GCBDA', 'GC3DYC4'], GeocachingService::getGeocachesIdFromText('gcbda matching start and end strings GC3DYC4'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text ThisGCIsNot matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC-3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC.3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC,3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text, splitted by newline GC
11 not matched'));
	}

	/**
	 * @group request
	 */
	public function testParseUrl(): void
	{
		$this->assertProcessSetup();

		$this->assertSame('50.087717,14.421150', GeocachingService::processStatic('https://www.geocaching.com/geocache/GC3DYC4')->getFirst()->__toString());
		$this->assertSame('50.087717,14.421150', GeocachingService::processStatic('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug')->getFirst()->__toString());
		$this->assertSame('50.087717,14.421150', GeocachingService::processStatic('https://coord.info/GC3DYC4')->getFirst()->__toString());
		$this->assertSame('50.087717,14.421150', GeocachingService::processStatic('https://www.geocaching.com/seek/cache_details.aspx?guid=df11c170-1af3-4ee1-853a-e97c1afe0722')->getFirst()->__toString());
	}

	/**
	 * @group request
	 */
	public function testParseUrlPremium(): void
	{
		$this->assertProcessSetup();

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Cannot show coordinates for geocache <a href="https://www.geocaching.com/geocache/GC2QB60">GC2QB60</a> - for Geocaching premium users only');
		GeocachingService::processStatic('https://www.geocaching.com/geocache/GC2QB60_chebsky-most?guid=8edaee5b-6723-4022-a295-8a21d990ef11')->getFirst()->__toString();
	}

	/**
	 * @group request
	 */
	public function testFindInText(): void
	{
		$this->assertProcessSetup();

		$collection = GeocachingService::findInText('GC3DYC4');
		$this->assertCount(1, $collection->getLocations());
		$this->assertSame('50.087717,14.421150', $collection[0]->__toString());
	}
}
