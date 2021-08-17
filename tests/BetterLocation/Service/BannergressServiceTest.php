<?php declare(strict_types=1);

use App\BetterLocation\Service\BannergressService;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\MiniCurl\Exceptions\InvalidResponseException;
use PHPUnit\Framework\TestCase;

final class BannergressServiceTest extends TestCase
{
	private function assertLocation(string $url, float $lat, float $lon): void
	{
		$collection = BannergressService::processStatic($url)->getCollection();
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertEqualsWithDelta($lat, $location->getLat(), 0.000001);
		$this->assertEqualsWithDelta($lon, $location->getLon(), 0.000001);
	}

	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://bannergress.com/map?lat=50.087451&lng=14.420671&zoom=15', BannergressService::getLink(50.087451, 14.420671));
		$this->assertSame('https://bannergress.com/map?lat=50.100000&lng=14.500000&zoom=15', BannergressService::getLink(50.1, 14.5));
		$this->assertSame('https://bannergress.com/map?lat=-50.200000&lng=14.600000&zoom=15', BannergressService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://bannergress.com/map?lat=50.300000&lng=-14.700001&zoom=15', BannergressService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://bannergress.com/map?lat=-50.400000&lng=-14.800008&zoom=15', BannergressService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		BannergressService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(BannergressService::isValidStatic('http://bannergress.com/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/barrie-skyline-f935'));
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/histórica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/長良川鉄道-乗りつぶし-観光編-adea'));
		$this->assertTrue(BannergressService::isValidStatic('https://bannergress.com/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'));

		// Invalid
		$this->assertFalse(BannergressService::isValidStatic('some invalid url'));
		$this->assertFalse(BannergressService::isValidStatic('https://bannergress.com'));
		$this->assertFalse(BannergressService::isValidStatic('http://bannergress.com'));
		$this->assertFalse(BannergressService::isValidStatic('https://bannergress.com/banner/'));
		$this->assertFalse(BannergressService::isValidStatic('http://www.some-domain.cz/'));
		$this->assertFalse(BannergressService::isValidStatic('http://www.some-domain.cz/some-path'));
	}

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
	 */
	public function testInvalid(): void
	{
		$this->expectException(InvalidResponseException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "https://api.bannergress.com/bnrs/aaaa-bbbb"');
		BannergressService::processStatic('https://bannergress.com/banner/aaaa-bbbb')->getCollection();
	}
}
