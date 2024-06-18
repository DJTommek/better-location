<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Bannergress\BannergressService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class BannergressServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
	}

	protected function getServiceClass(): string
	{
		return BannergressService::class;
	}

	protected function getShareLinks(): array
	{
		$this->revalidateGeneratedShareLink = false;

		return [
			'https://bannergress.com/map?lat=50.087451&lng=14.420671&zoom=15',
			'https://bannergress.com/map?lat=50.100000&lng=14.500000&zoom=15',
			'https://bannergress.com/map?lat=-50.200000&lng=14.600000&zoom=15', // round down
			'https://bannergress.com/map?lat=50.300000&lng=-14.700001&zoom=15', // round up
			'https://bannergress.com/map?lat=-50.400000&lng=-14.800008&zoom=15',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://bannergress.com/map?lat=50.087451&lng=14.420671&zoom=15', \App\BetterLocation\Service\Bannergress\BannergressService::getLink(50.087451, 14.420671));
		$this->assertSame('https://bannergress.com/map?lat=50.100000&lng=14.500000&zoom=15', \App\BetterLocation\Service\Bannergress\BannergressService::getLink(50.1, 14.5));
		$this->assertSame('https://bannergress.com/map?lat=-50.200000&lng=14.600000&zoom=15', \App\BetterLocation\Service\Bannergress\BannergressService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://bannergress.com/map?lat=50.300000&lng=-14.700001&zoom=15', \App\BetterLocation\Service\Bannergress\BannergressService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://bannergress.com/map?lat=-50.400000&lng=-14.800008&zoom=15', \App\BetterLocation\Service\Bannergress\BannergressService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		\App\BetterLocation\Service\Bannergress\BannergressService::getLink(50.087451, 14.420671, true);
	}

	public static function isValidProvider(): array
	{
		return [
			[true, 'http://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'],
			[true, 'https://bannergress.com/banner/barrie-skyline-f935'],
			[true, 'https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'],
			[true, 'https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd'],
			[true, 'https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea'],
			[true, 'https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'],

			[false, 'some invalid url'],
			[false, 'https://bannergress.com'],
			[false, 'http://bannergress.com'],
			[false, 'https://bannergress.com/banner/'],
			[false, 'http://www.some-domain.cz/'],
			[false, 'http://www.some-domain.cz/some-path'],
		];
	}

	public static function processProvider(): array
	{
		return [
			[
				50.087213,
				14.425674,
				'<a href="https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b">Bannergress Czech cubism  and its representative</a> <a href="https://api.bannergress.com/bnrs/pictures/1bc82f3f243f77d4360e5a063194665d">🖼</a>',
				[
					'36 missions, 11.2 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F03c465c460b44ccfa5659f8ce20c2fe4.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F03c465c460b44ccfa5659f8ce20c2fe4.1c">Czech cubism  and its representative 1 📱</a> <a href="https://intel.ingress.com/mission/03c465c460b44ccfa5659f8ce20c2fe4.1c">🖥</a> <a href="https://lh3.googleusercontent.com/IAOb9xC7aSjvEjo_yG75bMwwz-RPuhWMVWFrjbyW0ZxQlVv8qg8l84XUBOjy-c7Z1DASJB0q1_l23AftLCA">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F48ca04bd653a43eea68b5df595227ac4.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087213%2C14.425674">Dům u Černé matky Boží 📱</a> <a href="https://intel.ingress.com/intel?pll=50.087213,14.425674">🖥</a>',
				],
				'https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b',
			],
			[
				35.445393,
				137.019408,
				'<a href="https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea">Bannergress 長良川鉄道 乗りつぶし 観光編</a> <a href="https://api.bannergress.com/bnrs/pictures/8ef227000bb990adf1b5016822d59f96">🖼</a>',
				[
					'6 missions, 66.2 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9eed921e28ba4b0d9c0cbe33c7625949.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9eed921e28ba4b0d9c0cbe33c7625949.1c">1/6 長良川鉄道 乗りつぶし 観光編 📱</a> <a href="https://intel.ingress.com/mission/9eed921e28ba4b0d9c0cbe33c7625949.1c">🖥</a> <a href="https://lh3.googleusercontent.com/I76TstcCXBSYQwPzIght5t9o6MUb4O-iNmJWgYoo39QPx5vcSEN1AwY_GfJc-sOtNsZRNx5CxCNeCCUe7C0">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F95ed7ea05c8643748d6e11e3d3e6634a.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D35.445393%2C137.019408">JR美濃太田駅 📱</a> <a href="https://intel.ingress.com/intel?pll=35.445393,137.019408">🖥</a>',
				],
				'https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea',
			],
			[
				35.445393,
				137.019408,
				'<a href="https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea">Bannergress 長良川鉄道 乗りつぶし 観光編</a> <a href="https://api.bannergress.com/bnrs/pictures/8ef227000bb990adf1b5016822d59f96">🖼</a>',
				[
					'6 missions, 66.2 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9eed921e28ba4b0d9c0cbe33c7625949.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9eed921e28ba4b0d9c0cbe33c7625949.1c">1/6 長良川鉄道 乗りつぶし 観光編 📱</a> <a href="https://intel.ingress.com/mission/9eed921e28ba4b0d9c0cbe33c7625949.1c">🖥</a> <a href="https://lh3.googleusercontent.com/I76TstcCXBSYQwPzIght5t9o6MUb4O-iNmJWgYoo39QPx5vcSEN1AwY_GfJc-sOtNsZRNx5CxCNeCCUe7C0">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F95ed7ea05c8643748d6e11e3d3e6634a.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D35.445393%2C137.019408">JR美濃太田駅 📱</a> <a href="https://intel.ingress.com/intel?pll=35.445393,137.019408">🖥</a>',
				],
				'https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea',
			],
			[
				-25.3414,
				-57.508801,
				'<a href="https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd">Bannergress Histórica Catedral de San Lorenzo</a> <a href="https://api.bannergress.com/bnrs/pictures/1c41b1923dec9abc1f3268879247764d">🖼</a>',
				[
					'36 missions, 12.2 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fbf5438fb646c4ff299433d00c8f52fec.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fbf5438fb646c4ff299433d00c8f52fec.1c">Histórica Catedral de San Lorenzo 1/36 📱</a> <a href="https://intel.ingress.com/mission/bf5438fb646c4ff299433d00c8f52fec.1c">🖥</a> <a href="https://lh3.googleusercontent.com/BX9e64w3Nw3x3Y04cG7HScYalx8Fc5jWOrmc_jxh7c4TO30dRYj1ouXu16bqi_2MEJ4mfSbQ7vPievMJb7DQ">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F7fd4e9a641354c2faabfa98d2ae5a21a.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-25.341400%2C-57.508801">Homenaje Niños De San Lorenzo 📱</a> <a href="https://intel.ingress.com/intel?pll=-25.341400,-57.508801">🖥</a>',
				],
				'https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd',
			],
			[
				-25.3414,
				-57.508801,
				'<a href="https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd">Bannergress Histórica Catedral de San Lorenzo</a> <a href="https://api.bannergress.com/bnrs/pictures/1c41b1923dec9abc1f3268879247764d">🖼</a>',
				[
					'36 missions, 12.2 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fbf5438fb646c4ff299433d00c8f52fec.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fbf5438fb646c4ff299433d00c8f52fec.1c">Histórica Catedral de San Lorenzo 1/36 📱</a> <a href="https://intel.ingress.com/mission/bf5438fb646c4ff299433d00c8f52fec.1c">🖥</a> <a href="https://lh3.googleusercontent.com/BX9e64w3Nw3x3Y04cG7HScYalx8Fc5jWOrmc_jxh7c4TO30dRYj1ouXu16bqi_2MEJ4mfSbQ7vPievMJb7DQ">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F7fd4e9a641354c2faabfa98d2ae5a21a.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-25.341400%2C-57.508801">Homenaje Niños De San Lorenzo 📱</a> <a href="https://intel.ingress.com/intel?pll=-25.341400,-57.508801">🖥</a>',
				],
				'https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd',
			],
			[
				-41.287008,
				174.778374,
				'<a href="https://bannergress.com/banner/a-visit-to-te-papa-dffa">Bannergress A Visit to Te Papa</a> <a href="https://api.bannergress.com/bnrs/pictures/eeb9e2f94ff522ee03889b0ca5845d3b">🖼</a>',
				[
					'3 missions, 1.1 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F853b2312aae24d0986c1ab9e22a609bd.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F853b2312aae24d0986c1ab9e22a609bd.1c">A Visit to Te Papa 01 of 03 📱</a> <a href="https://intel.ingress.com/mission/853b2312aae24d0986c1ab9e22a609bd.1c">🖥</a> <a href="https://lh3.googleusercontent.com/Ib0teN1w4L5fHzODwtqnz8BPP9IOoHqV3bXvA_VseGCGtpTFgh_gJ3CNKNlMJN4gd8QQ6-9snIJDmoJ0yEQ1">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Ffd5d393d6117400d9cb7a40bfd1d68a5.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-41.287008%2C174.778374">Frank Kitts Park 📱</a> <a href="https://intel.ingress.com/intel?pll=-41.287008,174.778374">🖥</a>',
				],
				'https://bannergress.com/banner/a-visit-to-te-papa-dffa',
			],
			[ // Contains < and > characters in mosaic title and mosaic missions
				35.828688,
				139.803912,
				'<a href="https://bannergress.com/banner/mdsss-mission-day-埼玉六宿-f249">Bannergress &lt;MDSSS&gt;Mission Day 埼玉六宿</a> <a href="https://api.bannergress.com/bnrs/pictures/67ebaaf299ec15e4fa74b358f582cf90">🖼</a>',
				[
					'18 missions, 191.9 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F8ec5d5cb603947ea89dda655918c6337.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F8ec5d5cb603947ea89dda655918c6337.1c">&lt;MDSSS草1&gt;草加宿の歴史探訪 📱</a> <a href="https://intel.ingress.com/mission/8ec5d5cb603947ea89dda655918c6337.1c">🖥</a> <a href="https://lh3.googleusercontent.com/JvSw_chppp0lQgdplLz0HE2WW7ibc-u4yHOA8bCH3Slz2EnFi6hcvJYzmN63k7PD5RiYyY7AXjsWw0n49dZk">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fde67af6909be49d6bab104e5486f64ed.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D35.828688%2C139.803912">おせんさんの像 📱</a> <a href="https://intel.ingress.com/intel?pll=35.828688,139.803912">🖥</a>',
				],
				'https://bannergress.com/banner/mdsss-mission-day-%E5%9F%BC%E7%8E%89%E5%85%AD%E5%AE%BF-f249',
			],
			[ // Mosaic contains warning
				51.340404,
				12.375222,
				'<a href="https://bannergress.com/banner/altes-rathaus-leipzig-e69b">Bannergress Altes Rathaus Leipzig</a> <a href="https://api.bannergress.com/bnrs/pictures/6726ae2da8f01f37c32bbff9fa677da7">🖼</a>',
				[
					'24 missions, 7.6 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb7532be89bc44a9694bdb412e7d30725.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Fb7532be89bc44a9694bdb412e7d30725.1c">Altes Rathaus Leipzig 1#24 📱</a> <a href="https://intel.ingress.com/mission/b7532be89bc44a9694bdb412e7d30725.1c">🖥</a> <a href="https://lh3.googleusercontent.com/Le0eMqMKmXaowSNA_Ko0Xm7UoC-pk9_IdVOrmlYqns2yEOftJc-qoVuBMipgDBs1qf41P8o4CcqvCni6d5Ss">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fcc04a0b5111c42a9a8c82596891eeccb.11&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D51.340404%2C12.375222">Stadtgeschichtliches Museum 📱</a> <a href="https://intel.ingress.com/intel?pll=51.340404,12.375222">🖥</a>',
					'⚠ Mission 19 only playable during opening hours (Monday to Friday between 7am and 8pm and Saturday between 7am and 3pm)',
				],
				'https://bannergress.com/banner/altes-rathaus-leipzig-e69b',
			],
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new BannergressService($this->httpTestClients->mockedRequestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
	}

	/**
	 * @group request
	 * @dataProvider processProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedDescriptions, string $input): void
	{
		$service = new BannergressService($this->httpTestClients->realRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescriptions, $input);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $expectedPrefix, array $expectedDescriptions, string $input): void
	{
		$service = new BannergressService($this->httpTestClients->offlineRequestor);
		$this->testProcess($service, $expectedLat, $expectedLon, $expectedPrefix, $expectedDescriptions, $input);
	}

	private function testProcess(
		BannergressService $service,
		float $expectedLat,
		float $expectedLon,
		string $expectedPrefix,
		array $expectedDescriptions,
		string $input,
	): void {
		$location = $this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
		$descriptions = $location->getDescriptions();
		$this->assertSame($expectedPrefix, $location->getPrefixMessage());

		$this->assertCount(count($expectedDescriptions), $descriptions);

		foreach ($expectedDescriptions as $key => $expectedDescriptionText) {
			$this->assertSame($expectedDescriptionText, $descriptions[$key]->content);
		}
	}

	/**
	 * Pages, that do not have any location
	 *
	 * @group request
	 */
	public function testInvalidReal(): void
	{
		$service = new BannergressService($this->httpTestClients->realRequestor);
		$service->setInput('https://bannergress.com/banner/aaaa-bbbb');
		$this->assertTrue($service->validate());
		$service->process();
		$this->assertCount(0, $service->getCollection());
	}

	public static function mockedPlaceProvider(): array
	{
		return [
			[
				50.084219,
				14.423319,
				[ // descriptions
					'60 missions, 13.9 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2Ffb9808d04e0c4df4af24bfef06fb8c3a.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2Ffb9808d04e0c4df4af24bfef06fb8c3a.1c">Codex Gigas 01 📱</a> <a href="https://intel.ingress.com/mission/fb9808d04e0c4df4af24bfef06fb8c3a.1c">🖥</a> <a href="https://lh3.googleusercontent.com/ujIbgfnJaW8dY5WjWczYDOB0FIeeFP9IG1Mb9fUqy3507TY3166-9RXa4ZIyREDia1GHkfAL3K-_2Ff5KThw">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fcc0e0fa76b704743802068c49a745a9b.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.084219%2C14.423319">Hotel Ruche 📱</a> <a href="https://intel.ingress.com/intel?pll=50.084219,14.423319">🖥</a>',
				],
				'https://bannergress.com/banner/codex-gigas-fb0f',
				__DIR__ . '/fixtures/codex-gigas-fb0f.json',
			],
			[
				37.206308,
				126.832195,
				[ // descriptions
					'6 missions, 2.8 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F21da61232d1f4a49b51f72e8abc5ab60.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F21da61232d1f4a49b51f72e8abc5ab60.1c">Enlightened city 1/6 📱</a> <a href="https://intel.ingress.com/mission/21da61232d1f4a49b51f72e8abc5ab60.1c">🖥</a> <a href="https://lh3.googleusercontent.com/647uFaM0vvc4SZK9uUnw2H0nNThe3nZq60E-cWeOECqa0uJ30ePy_wtybjyzA_4eO18CEZipcoTnF0xsWVvNh55Alf0Y8DfT4A">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F3597fde938733236b23dfa72dc705584.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D37.206308%2C126.832195">남양아이파크아파트 노인회관 📱</a> <a href="https://intel.ingress.com/intel?pll=37.206308,126.832195">🖥</a>',
				],
				'https://bannergress.com/banner/enlightened-city-e86b',
				__DIR__ . '/fixtures/enlightened-city-e86b.json',
			],
			[
				-41.279523,
				174.780023,
				[ // descriptions
					'3 missions, 1.4 km',
					'First mission: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9e38310b13f64b908cb43bb42426441a.1c&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fmission%2F9e38310b13f64b908cb43bb42426441a.1c">A Trip to the City Gallery Wellington 01 of 03 📱</a> <a href="https://intel.ingress.com/mission/9e38310b13f64b908cb43bb42426441a.1c">🖥</a> <a href="https://lh3.googleusercontent.com/Yd4UhyDTi4O4bA1d58ETG_Dc_D7FMflniQ0djdhcL6qv6LBFSo7LoGpvP3DG__vczvh4BXM1lw9t1j-GqYVj">🖼</a>',
					'First portal: <a href="https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Fcbabba2423b44f8cb7a844ca7c4c4208.11&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D-41.279523%2C174.780023">Wellington Railway Station 📱</a> <a href="https://intel.ingress.com/intel?pll=-41.279523,174.780023">🖥</a>',
				],
				'https://bannergress.com/banner/a-trip-to-the-city-gallery-wellington-d6c0',
				__DIR__ . '/fixtures/a-trip-to-the-city-gallery-wellington-d6c0.json',
			],
		];
	}

	/**
	 * @dataProvider mockedPlaceProvider
	 */
	public function testProcessPlaceMocked(
		float $expectedLat,
		float $expectedLon,
		array $expectedDescriptions,
		string $inputUrl,
		string $mockedJsonFile,
	): void {
		$this->httpTestClients->mockHandler->append(new \GuzzleHttp\Psr7\Response(200, body: file_get_contents($mockedJsonFile)));

		$service = new BannergressService($this->httpTestClients->mockedRequestor);
		$location = $this->assertServiceLocation($service, $inputUrl, $expectedLat, $expectedLon);

		$descriptions = $location->getDescriptions();
		foreach ($descriptions as $key => $value) {
			$expectedDescription = $expectedDescriptions[$key];
			$this->assertSame($expectedDescription, (string)$value);
		}
	}

	public function testInvalidMocked(): void
	{
		$this->httpTestClients->mockHandler->append(new \GuzzleHttp\Psr7\Response(404));

		$service = new BannergressService($this->httpTestClients->mockedRequestor);
		$service->setInput('https://bannergress.com/banner/some-non-existing-banner-test-a1b2');
		$this->assertTrue($service->validate());
		$service->process();

		$this->assertCount(0, $service->getCollection());
	}
}
