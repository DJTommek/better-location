<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\GeocachingService;
use App\Config;
use Tests\HttpTestClients;

final class GeocachingServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return GeocachingService::class;
	}

	protected function getShareLinks(): array
	{
		return [
			'https://www.geocaching.com/play/map?lat=50.087451&lng=14.420671',
			'https://www.geocaching.com/play/map?lat=50.100000&lng=14.500000',
			'https://www.geocaching.com/play/map?lat=-50.200000&lng=14.600000', // round down
			'https://www.geocaching.com/play/map?lat=50.300000&lng=-14.700001', // round up
			'https://www.geocaching.com/play/map?lat=-50.400000&lng=-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isValidProvider(): array
	{
		return [
			// geocaching.com geocache
			[true, 'https://www.geocaching.com/geocache/GC3DYC4'],
			[true, 'http://www.geocaching.com/geocache/GC3DYC4'],
			[true, 'https://geocaching.com/geocache/GC3DYC4'],
			[true, 'http://geocaching.com/geocache/GC3DYC4'],
			[true, 'https://GEOcacHing.cOm/geocache/GC3dyC4'],

			// geocaching.com geocache with name
			[true, 'https://www.geocaching.com/geocache/GC3DYC4_find-the-bug'],
			[true, 'https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722'],

			// geocaching.com geocache guid
			[true, 'https://www.geocaching.com/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'],
			[true, 'https://www.geocaching.com/seek/cache_details.aspx?GUID=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'],

			// geocaching.com map geocache
			[true, 'https://www.geocaching.com/play/map/GC3DYC4'],
			[true, 'https://www.geocaching.com/play/map/gC3dyC4'],

			[false, 'https://www.geocaching.com'],
			[false, 'https://www.geocaching.com/geocache/'],
			[false, 'https://www.geocaching.com/geocache/AA3DYC4'],

			[false, 'https://www.geocaching.com/seek/cache_details.aspx?guid={498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4}'],
			[false, 'https://www.geocaching.com/seek/cache_details.aaa?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'],
			[false, 'https://www.geocaching.com/seek/blabla.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'],
			[false, 'https://coord.info/seek/cache_details.aspx?guid=498e4dfa-ad2d-4bcc-8e47-93eb17e3cdd4'],

			// geocaching.com map search
			[true, 'https://www.geocaching.com/play/map?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance'],
			[true, 'https://www.geocaching.com/play/map/?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance'],
			[true, 'https://www.geocaching.com/play/map?lat=-50.08&lng=14.42115&zoom=18&asc=true&sort=distance'],
			[true, 'https://www.geocaching.com/play/map?lat=-51.705545&lng=-57.933311&zoom=12&asc=true&sort=distance&sw=1'],

			[false, 'https://www.geocaching.com/play/map?lat=-51.aaa&lng=123&zoom=12&asc=true&sort=distance&sw=1'], // invalid lat
			[false, 'https://www.geocaching.com/play/map?lat=-51.705545&lng=123aa&zoom=12&asc=true&sort=distance&sw=1'], // invalid lng
			[false, 'https://www.geocaching.com/play/map?lat=95&lng=123&zoom=12&asc=true&sort=distance&sw=1'], // lat over limit
			[false, 'https://www.geocaching.com/play/map?lat=49.5&lng=191.111&zoom=12&asc=true&sort=distance&sw=1'], // lng over limit

			// geocaching.com map browse
			[true, 'https://www.geocaching.com/map/#?ll=50.05821,14.457&z=16'],
			[true, 'https://www.geocaching.com/map/#?ll=-50.08,14.42115&z=9'],
			[true, 'https://www.geocaching.com/map/#?z=10&ll=-51.705545,-57.933311'],

			[false, 'https://www.geocaching.com/map/#?ll=50.aaa,14.457&z=16'], // invalid lat
			[false, 'https://www.geocaching.com/map/#?ll=50.05821,14.123aaa&z=16'], // invalid lng
			[false, 'https://www.geocaching.com/map/#?ll=95.05821,14.457&z=16'], // lat over limit
			[false, 'https://www.geocaching.com/map/#?ll=50.05821,194.457&z=16'], // lng over limit

			// coord.info map browse
			[true, 'http://coord.info/map?ll=50.05821,14.457&z=16'],
			[true, 'http://coord.info/map?ll=-50.08,14.42115&z=9'],
			[true, 'http://coord.info/map?z=10&ll=-51.705545,-57.933311'],

			[false, 'http://coord.info/map?ll=50.aaa,14.457&z=16'], // invalid lat
			[false, 'http://coord.info/map?ll=50.05821,14.123aaa&z=16'], // invalid lng
			[false, 'http://coord.info/map?ll=95.05821,14.457&z=16'], // lat over limit
			[false, 'http://coord.info/map?ll=50.05821,194.457&z=16'], // lng over limit

			// coord.info geocache
			[true, 'https://coord.info/GC3DYC4'],
			[true, 'https://www.coord.info/GC3DYC4'],
			[true, 'https://coOrD.INfo/Gc3dyC4'],

			[false, 'https://coord.info/AA3dyC4'],
			[false, 'https://coord.info/GC'],
		];
	}

	public static function coordsFromMapSearchUrlProvider(): array
	{
		return [
			[50.087717, 14.42115, 'https://www.geocaching.com/play/map?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance', GeocachingService::TYPE_MAP_SEARCH],
			[50.087717, 14.42115, 'https://www.geocaching.com/play/map/?lat=50.087717&lng=14.42115&zoom=18&asc=true&sort=distance', GeocachingService::TYPE_MAP_SEARCH],
			[-50.08, 14.42115, 'https://www.geocaching.com/play/map?lat=-50.08&lng=14.42115&zoom=18&asc=true&sort=distance', GeocachingService::TYPE_MAP_SEARCH],
			[-51.705545, -57.933311, 'https://www.geocaching.com/play/map?lat=-51.705545&lng=-57.933311&zoom=12&asc=true&sort=distance&sw=1', GeocachingService::TYPE_MAP_SEARCH],
		];
	}

	public static function coordsFromMapBrowseUrlProvider(): array
	{
		return [
			[50.05821, 14.457, 'https://www.geocaching.com/map/#?ll=50.05821,14.457&z=16', GeocachingService::TYPE_MAP_BROWSE],
			[-50.08, 14.42115, 'https://www.geocaching.com/map/#?ll=-50.08,14.42115&z=9', GeocachingService::TYPE_MAP_BROWSE],
			[-51.705545, -57.933311, 'https://www.geocaching.com/map/#?z=10&ll=-51.705545,-57.933311', GeocachingService::TYPE_MAP_BROWSE],
		];
	}

	public static function coordsFromMapCoordInfoUrlProvider(): array
	{
		return [
			[50.05821, 14.457, 'http://coord.info/map?ll=50.05821,14.457&z=16', GeocachingService::TYPE_MAP_COORD],
			[-50.08, 14.42115, 'http://coord.info/map?ll=-50.08,14.42115&z=9', GeocachingService::TYPE_MAP_COORD],
			[-51.705545, -57.933311, 'http://coord.info/map?z=10&ll=-51.705545,-57.933311', GeocachingService::TYPE_MAP_COORD],
		];
	}

	public static function geocacheIdUrlProvider(): array
	{
		return [
			[50.087717, 14.421150, 'https://www.geocaching.com/geocache/GC3DYC4', GeocachingService::TYPE_CACHE],
			[50.087717, 14.421150, 'https://www.geocaching.com/geocache/GC3DYC4_find-the-bug', GeocachingService::TYPE_CACHE],
			[50.087717, 14.421150, 'https://coord.info/GC3DYC4', GeocachingService::TYPE_CACHE],
		];
	}

	public static function geocacheGuidUrlProvider(): array
	{
		return [
			[50.087717, 14.421150, 'https://www.geocaching.com/seek/cache_details.aspx?guid=df11c170-1af3-4ee1-853a-e97c1afe0722', GeocachingService::TYPE_CACHE],
		];
	}

	public static function geocacheIdFromTextProvider(): array
	{
		return [
			[[[50.087717, 14.421150]], 'GC3DYC4'],
			[[[29.359067, -89.451500], [40.636933, -89.043150]], 'Some random text, geocache GC1111 and another here: gc12aBd'],
		];
	}

	public static function geocacheIdPremiumUrlProvider(): array
	{
		return [
			['https://www.geocaching.com/geocache/GC2QB60_chebsky-most?guid=8edaee5b-6723-4022-a295-8a21d990ef11'],
		];
	}

	public static function cacheIdFromUrlGeocachingComProvider(): array
	{
		return [
			['GC3DYC4', 'https://www.geocaching.com/geocache/GC3DYC4'],
			['GC3DYC4', 'https://geocaching.com/geocache/GC3DYC4'],
			['GC3DYC4', 'https://GEOcacHing.cOm/geocache/GC3dyC4'],
			// including name
			['GC3DYC4', 'https://www.geocaching.com/geocache/GC3DYC4_find-the-bug'],
			['GC3DYC4', 'https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722'],
			// from map
			['GC3DYC4', 'https://www.geocaching.com/play/map/GC3DYC4'],
			['GC3DYC4', 'https://www.geocaching.com/play/map/gC3dyC4'],

			[null, 'https://www.geocaching.com/play/map/gc'], // missing ID after prefix],
			[null, 'https://www.geocaching.com/play/map/BB3DYC4'], // missing correct prefix],
			[null, 'https://www.geocaching.com/aaaaaaaa/GC3dyC4'], // wrong path],
			[null, 'https://www.geocaching.com/geocache/GC3DYC4-find-the-bug'], // invalid divider before ID and name],
		];
	}

	public static function cacheIdFromUrlCoordInfoProvider(): array
	{
		return [
			['GC3DYC4', 'https://coord.info/GC3DYC4'],
			['GC3DYC4', 'https://www.coord.info/GC3DYC4'],
			['GC3DYC4', 'https://coOrD.INfo/Gc3dyC4'],

			[null, 'https://coord.info/AA3dyC4'],
			[null, 'https://coord.info/GC'],
		];
	}

	public static function cacheIdFromTextProvider(): array
	{
		return [
			[
				['GC1111', 'GC12ABD'],
				'Some random text, geocache GC1111 newline
gc12aBd, case in-sensitive, gc-blabla, gc.abc',
			],
			[
				['GC1111', 'GC12ABD'],
				'Some random text, geocache GC1111 newline gc12aBd
, case in-sensitive, gc-blabla, gc.abc',
			],
			[
				['GC1111', 'GC12ABD'],
				'Some random text, geocache GC1111 newline 
gc12aBd
, case in-sensitive, gc-blabla, gc.abc',
			],
			[['GCBDA', 'GC3DYC4'], 'gcbda matching start and end strings GC3DYC4'],
			[[], 'Some random text ThisGCIsNot matched'],
			[[], 'Some random text GC-3DYC4 splitted, not matched'],
			[[], 'Some random text GC.3DYC4 splitted, not matched'],
			[[], 'Some random text GC,3DYC4 splitted, not matched'],
			[
				[],
				'Some random text, splitted by newline GC
11 not matched',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new GeocachingService();
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @dataProvider coordsFromMapSearchUrlProvider
	 * @dataProvider coordsFromMapBrowseUrlProvider
	 * @dataProvider coordsFromMapCoordInfoUrlProvider
	 */
	public function testProcessCoordsFromUrl(float $expectedLat, float $expectedLon, string $input, string $expectedSourceType): void
	{
		$geocachingClient = $this->createGeocachingClientMocked();
		$service = new GeocachingService($geocachingClient);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}

	/**
	 * @dataProvider geocacheIdUrlProvider
	 * @dataProvider geocacheGuidUrlProvider
	 * @group request
	 */
	public function testProcessGeocacheIdFromUrlReal(float $expectedLat, float $expectedLon, string $input, string $expectedSourceType): void
	{
		$geocachingClient = $this->createGeocachingClientReal();
		$service = new GeocachingService($geocachingClient);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}

	/**
	 * @dataProvider geocacheIdUrlProvider
	 */
	public function testProcessGeocacheIdFromUrlOffline(float $expectedLat, float $expectedLon, string $input, string $expectedSourceType): void
	{
		$geocachingClient = $this->createGeocachingClientOffline();
		$service = new GeocachingService($geocachingClient);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, $expectedSourceType);
	}

	/**
	 * @dataProvider geocacheIdPremiumUrlProvider
	 * @group request
	 */
	public function testParseUrlPremiumReal(string $url): void
	{
		$geocachingClient = $this->createGeocachingClientReal();
		$this->testParseUrlPremium($geocachingClient, $url);
	}

	/**
	 * @dataProvider geocacheIdPremiumUrlProvider
	 */
	public function testParseUrlPremiumOffline(string $url): void
	{
		$geocachingClient = $this->createGeocachingClientOffline();
		$this->testParseUrlPremium($geocachingClient, $url);
	}

	private function testParseUrlPremium(\App\Geocaching\Client $geocachingClient, string $url): void
	{
		$service = new GeocachingService($geocachingClient);

		$service->setInput($url);
		$this->assertTrue($service->validate());

		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Cannot show coordinates for geocache <a href="https://www.geocaching.com/geocache/GC2QB60">GC2QB60</a> - for Geocaching premium users only');

		$service->process();
	}

	/**
	 * @dataProvider geocacheIdFromTextProvider
	 * @group request
	 */
	public function testFindInText(array $expectedCoordsArray, string $text): void
	{
		$this->skipIfGeocachingNotSetup();

		$collection = GeocachingService::findInText($text);
		$this->assertSame(count($expectedCoordsArray), count($collection));

		foreach ($collection as $key => $betterLocation) {
			[$expectedLat, $expectedLon] = $expectedCoordsArray[$key];
			$this->assertSame($expectedLat, $betterLocation->getLat());
			$this->assertSame($expectedLon, $betterLocation->getLon());
			$this->assertSame(GeocachingService::TYPE_CACHE, $betterLocation->getSourceType());
		}
	}

	/**
	 * @dataProvider cacheIdFromUrlGeocachingComProvider
	 * @dataProvider cacheIdFromUrlCoordInfoProvider
	 */
	public function testGetCacheIdFromUrl(?string $expectedId, string $input): void
	{
		$url = new \Nette\Http\Url($input);
		$realId = GeocachingService::getGeocacheIdFromUrl($url);
		$this->assertSame($expectedId, $realId);
	}

	/**
	 * @dataProvider cacheIdFromTextProvider
	 */
	public function testGetGeocachesIdFromText(array $expectedIds, string $input): void
	{
		$realIds = GeocachingService::getGeocachesIdFromText($input);
		$this->assertSame($expectedIds, $realIds);
	}

	private function skipIfGeocachingNotSetup(): void
	{
		if (!Config::isGeocaching()) {
			$this->markTestSkipped('Geocaching service is not properly configured.');
		}
	}

	private function createGeocachingClientReal(): \App\Geocaching\Client
	{
		$this->skipIfGeocachingNotSetup();
		return new \App\Geocaching\Client($this->httpTestClients->realRequestor, Config::GEOCACHING_COOKIE);
	}

	private function createGeocachingClientMocked(): \App\Geocaching\Client
	{
		return new \App\Geocaching\Client($this->httpTestClients->mockedRequestor, '');
	}

	private function createGeocachingClientOffline(): \App\Geocaching\Client
	{
		return new \App\Geocaching\Client($this->httpTestClients->offlineRequestor, '');
	}
}
