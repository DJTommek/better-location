<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\GeocachingService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../src/bootstrap.php';

final class GeocachingServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.087451&lng=14.420671', GeocachingService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.100000&lng=14.500000', GeocachingService::getLink(50.1, 14.5));
		$this->assertEquals('https://www.geocaching.com/play/map?lat=-50.200000&lng=14.600000', GeocachingService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://www.geocaching.com/play/map?lat=50.300000&lng=-14.700001', GeocachingService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://www.geocaching.com/play/map?lat=-50.400000&lng=-14.800008', GeocachingService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		GeocachingService::getLink(50.087451, 14.420671, true);
	}

	public function testIsUrl(): void
	{
		// geocaching.com geocache
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('http://www.geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('https://geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('http://geocaching.com/geocache/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('https://GEOcacHing.cOm/geocache/GC3dyC4'));

		// geocaching.com geocache with name
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug'));
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722'));

		// geocaching.com geocache guid
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/seek/cache_details.aspx?GUID=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));

		// geocaching.com map geocache
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/play/map/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('https://www.geocaching.com/play/map/gC3dyC4'));

		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com'));
		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com/geocache/'));
		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com/geocache/AA3DYC4'));

		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com/seek/cache_details.aspx?guid={498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}'));
		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com/seek/cache_details.aaa?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertFalse(GeocachingService::isUrl('https://www.geocaching.com/seek/blabla.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));
		$this->assertFalse(GeocachingService::isUrl('https://coord.info/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'));

		// coord.info geocache
		$this->assertTrue(GeocachingService::isUrl('https://coord.info/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('https://www.coord.info/GC3DYC4'));
		$this->assertTrue(GeocachingService::isUrl('https://coOrD.INfo/Gc3dyC4'));

		$this->assertFalse(GeocachingService::isUrl('https://coord.info/AA3dyC4'));
		$this->assertFalse(GeocachingService::isUrl('https://coord.info/GC'));
	}


	public function testGetCacheIdFromUrlGeocachingCom(): void
	{
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/geocache/GC3DYC4'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://geocaching.com/geocache/GC3DYC4'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://GEOcacHing.cOm/geocache/GC3dyC4'));
		// including name
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722'));
		// from map
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/play/map/GC3DYC4'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/play/map/gC3dyC4'));

		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/play/map/gc')); // missing ID after prefix
		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/play/map/BB3DYC4')); // missing correct prefix
		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/aaaaaaaa/GC3dyC4')); // wrong path
		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://www.geocaching.com/geocache/GC3DYC4-find-the-bug')); // invalid divider before ID and name
	}

	public function testGetCacheIdFromUrlCoordInfo(): void
	{
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://coord.info/GC3DYC4'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://www.coord.info/GC3DYC4'));
		$this->assertEquals('GC3DYC4', GeocachingService::getCacheIdFromUrl('https://coOrD.INfo/Gc3dyC4'));

		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://coord.info/AA3dyC4'));
		$this->assertNull(GeocachingService::getCacheIdFromUrl('https://coord.info/GC'));
	}

	public function testGetCoordsFromMapUrl(): void
	{
		$this->assertEquals([50.087717, 14.42115], GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance'));
		$this->assertEquals([-50.08, 14.42115], GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=-50.08&lng=14.42115&zoom=18&asc=true&sort=distance'));
		$this->assertEquals([-51.705545, -57.933311], GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=-51.705545&lng=-57.933311&zoom=12&asc=true&sort=distance&sw=1'));

		$this->assertNull(GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=-51.aaa&lng=123&zoom=12&asc=true&sort=distance&sw=1')); // invalid lat
		$this->assertNull(GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=-51.705545&lng=123aa&zoom=12&asc=true&sort=distance&sw=1')); // invalid lng
		$this->assertNull(GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=95&lng=123&zoom=12&asc=true&sort=distance&sw=1')); // lat over limit
		$this->assertNull(GeocachingService::getCoordsFromMapUrl('https://www.geocaching.com/play/map?lat=49.5&lng=191.111&zoom=12&asc=true&sort=distance&sw=1')); // lng over limit
	}

	public function testGetGeocachesIdFromText(): void {
		$this->assertSame(['GC1111', 'gc12aBd'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline
gc12aBd, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['GC1111', 'gc12aBd'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline gc12aBd
, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['GC1111', 'gc12aBd'], GeocachingService::getGeocachesIdFromText('Some random text, geocache GC1111 newline 
gc12aBd
, case in-sensitive, gc-blabla, gc.abc'));
		$this->assertSame(['gcbda', 'GC3DYC4'], GeocachingService::getGeocachesIdFromText('gcbda matching start and end strings GC3DYC4'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text ThisGCIsNot matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC-3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC.3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text GC,3DYC4 splitted, not matched'));
		$this->assertSame([], GeocachingService::getGeocachesIdFromText('Some random text, splitted by newline GC
11 not matched'));
	}
}
