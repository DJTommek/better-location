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
			[50.087213, 14.425674, 'https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'],
			[35.445393, 137.019408, 'https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea'],
			[35.445393, 137.019408, 'https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'],
			[-25.3414, -57.508801, 'https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd'],
			[-25.3414, -57.508801, 'https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'],
			[-41.287008, 174.778374, 'https://bannergress.com/banner/a-visit-to-te-papa-dffa'],
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
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new BannergressService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new BannergressService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
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
