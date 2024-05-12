<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Bannergress\BannergressService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\MiniCurl\Exceptions\InvalidResponseException;
use App\Utils\Requestor;
use PHPUnit\Framework\TestCase;
use Tests\LocationTrait;
use Tests\TestUtils;

final class BannergressServiceTest extends TestCase
{
	use LocationTrait;

	private function assertLocation(string $url, float $lat, float $lon): void
	{
		$collection = \App\BetterLocation\Service\Bannergress\BannergressService::processStatic($url)->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertEqualsWithDelta($lat, $location->getLat(), 0.000001);
		$this->assertEqualsWithDelta($lon, $location->getLon(), 0.000001);
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

	public function testIsValid(): void
	{
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('http://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/barrie-skyline-f935'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea'));
		$this->assertTrue(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'));

		// Invalid
		$this->assertFalse(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('some invalid url'));
		$this->assertFalse(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('https://bannergress.com'));
		$this->assertFalse(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('http://bannergress.com'));
		$this->assertFalse(BannergressService::isValidStatic('https://bannergress.com/banner/'));
		$this->assertFalse(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('http://www.some-domain.cz/'));
		$this->assertFalse(\App\BetterLocation\Service\Bannergress\BannergressService::isValidStatic('http://www.some-domain.cz/some-path'));
	}

	/**
	 * @group request
	 */
	public function testProcessPlace(): void
	{
		$this->assertLocation('https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b', 50.087213, 14.425674);
		// This is failing only in unit tests but works if processed manually (via tester in browser or Telegram)
		// $this->assertLocation('https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea', 35.445393, 137.019408);
		$this->assertLocation('https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea', 35.445393, 137.019408);
		$this->assertLocation('https://bannergress.com/banner/a-visit-to-te-papa-dffa', -41.287008, 174.778374);
		$this->assertLocation('https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd', -25.3414, -57.508801);
		$this->assertLocation('https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd', -25.3414, -57.508801);
	}

	/**
	 * Pages, that do not have any location
	 *
	 * @group request
	 */
	public function testInvalid(): void
	{
		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "https://api.bannergress.com/bnrs/aaaa-bbbb"');
		\App\BetterLocation\Service\Bannergress\BannergressService::processStatic('https://bannergress.com/banner/aaaa-bbbb')->getCollection();
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
		[$httpClient, $mockHandler] = TestUtils::createMockedClientInterface();
		assert($httpClient instanceof \GuzzleHttp\Client);
		assert($mockHandler instanceof \GuzzleHttp\Handler\MockHandler);

		$mockHandler->append(new \GuzzleHttp\Psr7\Response(200, body: file_get_contents($mockedJsonFile)));
		$requestor = new Requestor($httpClient, TestUtils::getDevNullCache());

		$service = new BannergressService($requestor);
		$service->setInput($inputUrl);
		$this->assertTrue($service->isValid());
		$service->process();
		$collection = $service->getCollection();

		$this->assertOneInCollection($expectedLat, $expectedLon, null, $collection);

		$location = $collection->getFirst();
		$descriptions = $location->getDescriptions();
		foreach ($descriptions as $key => $value) {
			$expectedDescription = $expectedDescriptions[$key];
			$this->assertSame($expectedDescription, (string)$value);
		}
	}

	public function testInvalidMocked(): void {
		[$httpClient, $mockHandler] = TestUtils::createMockedClientInterface();
		assert($httpClient instanceof \GuzzleHttp\Client);
		assert($mockHandler instanceof \GuzzleHttp\Handler\MockHandler);

		$mockHandler->append(new \GuzzleHttp\Psr7\Response(404));
		$requestor = new Requestor($httpClient, TestUtils::getDevNullCache());

		$service = new BannergressService($requestor);
		$service->setInput('https://bannergress.com/banner/some-non-existing-banner-test-a1b2');
		$this->assertTrue($service->isValid());
		$service->process();

		$this->assertCount(0, $service->getCollection());
	}
}
