<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\BookingService;
use Tests\HttpTestClients;

final class BookingServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return BookingService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	/**
	 * @return array<array{bool, string}>
	 */
	public static function isValidProvider(): array
	{
		return [
			[
				true,
				'https://www.booking.com/hotel/at/ludwighaus-neukirchen-am-grossvenediger.html?label=gen173nr-1FCAEoggI46AdIM1gEaDqIAQKYATG4AQnIAQ_YAQHoAQH4AQKIAgGoAgO4AsvBmKcGwAIB0gIkMjFhODMyNmUtYWIzNS00MWMxLWFlYmItNjkyYzMwYmEwYzNm2AIF4AIB&sid=46dfebf8b003c46bf127c7b91dfc7404&aid=304142&ucfs=1&arphpl=1&checkin=2023-08-24&checkout=2023-08-25&group_adults=2&req_adults=2&no_rooms=1&group_children=0&req_children=0&hpos=1&hapos=1&sr_order=distance_from_search&nflt=price%3DCZK-min-2500-1&srpvid=d5bb6b0893f1020e&srepoch=1692803628&all_sr_blocks=780183211_336428765_2_0_0&highlighted_blocks=780183211_336428765_2_0_0&matching_block_id=780183211_336428765_2_0_0&sr_pri_blocks=780183211_336428765_2_0_0__9300&activeTab=htMap',
			],
			[true, 'https://www.booking.com/hotel/cz/city-pisek.html'],
			[true, 'https://www.booking.com/hotel/cz/city-pisek'],
			[true, 'https://booking.com/hotel/cz/city-pisek'],
			[true, 'http://booking.com/hotel/cz/city-pisek'],
			[true, 'https://www.booking.com/hotel/ua/kvartira-revutskogo-40-zhk-lebedinnyi.en-gb.html'],

			// Share URLs
			[true, 'https://www.booking.com/Share-ZjMBsq'],
			[true, 'https://www.booking.com/Share-a'],
			[false, 'https://www.booking.com/Share'],

			[false, 'non url'],
			[false, 'https://www.booking.com/'],
			[false, 'https://www.booking.com/hotel/'],
			[false, 'https://www.booking.com/hotel/cz/'],
			[false, 'https://www.booking.com/hotel/city-pisek.html'], // missing country in URL
			// Invalid domains
			[false, 'https://www.booking.bla/hotel/cz/city-pisek.html'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				49.307064,
				14.148334,
				'🇨🇿 Alsovo namesti 35, Písek, 39701, Czech Republic',
				'★5.8 Located on Písek’s main square, this hotel is just 656 feet from the town’s popular Stone Bridge.',
				'https://www.booking.com/hotel/cz/city-pisek.html',
			],
			[
				47.252174,
				12.273956,
				'🇦🇹 121 Marktstraße, 5741 Neukirchen am Großvenediger, Austria',
				'★6.9 Ludwighaus enjoys a location in Neukirchen am Großvenediger, 25 miles from Casino Kitzbuhel and 28 miles from Golfclub Kitzbühel Schwarzsee. 7.',
				'https://www.booking.com/hotel/at/ludwighaus-neukirchen-am-grossvenediger.html?label=gen173nr-1FCAEoggI46AdIM1gEaDqIAQKYATG4AQnIAQ_YAQHoAQH4AQKIAgGoAgO4AsvBmKcGwAIB0gIkMjFhODMyNmUtYWIzNS00MWMxLWFlYmItNjkyYzMwYmEwYzNm2AIF4AIB&sid=46dfebf8b003c46bf127c7b91dfc7404&aid=304142&ucfs=1&arphpl=1&checkin=2023-08-24&checkout=2023-08-25&group_adults=2&req_adults=2&no_rooms=1&group_children=0&req_children=0&hpos=1&hapos=1&sr_order=distance_from_search&nflt=price%3DCZK-min-2500-1&srpvid=d5bb6b0893f1020e&srepoch=1692803628&all_sr_blocks=780183211_336428765_2_0_0&highlighted_blocks=780183211_336428765_2_0_0&matching_block_id=780183211_336428765_2_0_0&sr_pri_blocks=780183211_336428765_2_0_0__9300&activeTab=htMap',
			],
			[
				50.403071,
				30.650218,
				'🇺🇦 40 вулиця Ревуцького, Kyiv, 02000, Ukraine',
				'★9.7 Situated in Kyiv, 11 km from The Motherland Monument and 11 km from International Exhibition Centre, Квартира з Панорамним Краєвидом features...',
				'https://www.booking.com/hotel/ua/kvartira-revutskogo-40-zhk-lebedinnyi.en-gb.html?label=gen173nr-1FCAEoggI46AdIM1gEaDqIAQKYATG4AQnIAQ_YAQHoAQH4AQKIAgGoAgO4AovVg68GwAIB0gIkOTlmNTVkZDYtNGNhZi00NTcyLThiYmMtNGUyMTdlNDUzMzBl2AIF4AIB-Share-xuMgWp%401709239146&sid=37aaa5b5c32f16b0d561a2b64ec501fc&aid=304142&ucfs=1&arphpl=1&checkin=2024-04-23&checkout=2024-04-25&dest_id=220&dest_type=country&group_adults=2&req_adults=2&no_rooms=1&group_children=0&req_children=0&hpos=1&hapos=1&sr_order=popularity&srpvid=fc9c7372edeb02d0&srepoch=1709569511&all_sr_blocks=1011908901_378082863_3_0_0&highlighted_blocks=1011908901_378082863_3_0_0&matching_block_id=1011908901_378082863_3_0_0&sr_pri_blocks=1011908901_378082863_3_0_0__201667&from=searchresults#hotelTmpl',
			],
			[
				35.730117,
				139.733157,
				'🇯🇵 170-0005 Tokyo-to, Toshima-ku Minamiotsuka 1-38-4, Japan',
				'★8.3 Set 300 metres from Koyasu Tenman-gu Sugawara Shrine and 400 metres from Sugamo Park, No4マンション#JR大塚駅徒歩5分 築浅 池袋 自主隔離やテレワークOK 固定Wifi 靠近池袋和新宿 從山手線大塚站步行5分鐘...',
				'https://www.booking.com/hotel/jp/satiberumannan-da-zhong-di-4mansiyon.en-gb.html?aid=304142&label=gen173nr-1FCAEoggI46AdIM1gEaDqIAQKYATG4AQnIAQ_YAQHoAQH4AQKIAgGoAgO4AovVg68GwAIB0gIkOTlmNTVkZDYtNGNhZi00NTcyLThiYmMtNGUyMTdlNDUzMzBl2AIF4AIB-Share-xuMgWp%401709239146&sid=c01c544eed324ddc9971041d2ce7b978&all_sr_blocks=714926413_328497140_3_0_0%3Bcheckin%3D2024-04-23%3Bcheckout%3D2024-04-25%3Bdist%3D0%3Bgroup_adults%3D2%3Bgroup_children%3D0%3Bhapos%3D1%3Bhighlighted_blocks%3D714926413_328497140_3_0_0%3Bhpos%3D1%3Bmatching_block_id%3D714926413_328497140_3_0_0%3Bno_rooms%3D1%3Breq_adults%3D2%3Breq_children%3D0%3Broom1%3DA%2CA%3Bsb_price_type%3Dtotal%3Bsr_order%3Dpopularity%3Bsr_pri_blocks%3D714926413_328497140_3_0_0__3025000%3Bsrepoch%3D1709569552%3Bsrpvid%3Ddeb173854b1001d0%3Btype%3Dtotal%3Bucfs%3D1#hotelTmpl',
			],
			[ // Review score is missing
				53.5479056,
				9.9611475,
				'🇩🇪 Gerhardstraße, 20359 Hamburg, Germany',
				'2 Great Apartment near Hans Albers Platz offers accommodations in Hamburg, 1.5 miles from Hamburg Fair and 1.6 miles from Miniatur Wunderland.',
				'https://www.booking.com/hotel/de/great-apartment-near-hans-albers-platz-hamburg.html?label=gen173nr-10CAEoggI46AdIM1gEaDqIAQGYATO4ARnIAQzYAQPoAQH4AQGIAgGoAgG4AvKy48cGwAIB0gIkODU2YTIyYjQtZWE1NS00MjM5LWE0MzktMTI0ZTliODc0MTE52AIB4AIB&aid=304142&ucfs=1&checkin=2025-12-27&checkout=2025-12-30&dest_id=-1785434&dest_type=city&group_adults=6&no_rooms=1&group_children=0&nflt=oos%3D1%3Bprice%3DCZK-min-13000-1%3Bentire_place_bedroom_count%3D3&srpvid=fa2b5da83be80582&srepoch=1761140047&matching_block_id=1474509001_419198816_6_0_0&atlas_src=sr_iw_title',
			],
		];
	}

	public static function processProviderRedirect(): array
	{
		return [
			[
				48.347567,
				24.438137,
				'🇺🇦 Urochysche Staische, Bukovel, 78593, Ukraine',
				'★8.0 This Polyanytsya Villa Vlad &amp; Spa hotel is 1.5 km from the Ski Lift R1 and features a Ukrainian restaurant and ski facilities.',
				'https://www.booking.com/Share-ZjMBsq',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new BookingService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 * @dataProvider processProviderRedirect
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedAddress, string $expectedDescription, string $input): void
	{
		$service = new BookingService($this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedAddress, $expectedDescription, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedAddress, string $expectedDescription, string $input): void
	{
		$service = new BookingService($this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedAddress, $expectedDescription, $input);
	}

	private function testProcess(BookingService $service, float $expectedLat, float $expectedLon, string $expectedAddress, string $expectedDescription, string $input): void
	{
		$location = $this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$descriptions = $location->getDescriptions();
		$this->assertSame($expectedDescription, (string)$descriptions[0]);
		$this->assertSame($expectedAddress, $location->getAddress());
	}
}
