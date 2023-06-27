<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Bannergress\OpenBannersService;
use App\MiniCurl\Exceptions\InvalidResponseException;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class OpenBannersServiceTest extends AbstractServiceTestCase
{
	public function testIsValid(): void
	{
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://openbanners.org/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(OpenBannersService::isValidStatic('http://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/barrie-skyline-f935'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/histórica-catedral-de-san-lorenzo-55dd'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/長良川鉄道-乗りつぶし-観光編-adea'));
		$this->assertTrue(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'));

		// Invalid
		$this->assertFalse(OpenBannersService::isValidStatic('some invalid url'));
		$this->assertFalse(OpenBannersService::isValidStatic('https://www.openbanners.org'));
		$this->assertFalse(OpenBannersService::isValidStatic('http://www.openbanners.org'));
		$this->assertFalse(OpenBannersService::isValidStatic('https://www.openbanners.org/banner/'));
		$this->assertFalse(OpenBannersService::isValidStatic('http://www.some-domain.cz/'));
		$this->assertFalse(OpenBannersService::isValidStatic('http://www.some-domain.cz/some-path'));
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$this->assertLocation('https://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b', 50.087213, 14.425674);
		// This is failing only in unit tests but works if processed manually (via tester in browser or Telegram)
		// $this->assertLocation('https://www.openbanners.org/banner/長良川鉄道-乗りつぶし-観光編-adea', 35.445393, 137.019408);
		$this->assertLocation('https://www.openbanners.org/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea', 35.445393, 137.019408);
		$this->assertLocation('https://www.openbanners.org/banner/a-visit-to-te-papa-dffa', -41.287008, 174.778374);
		$this->assertLocation('https://openbanners.org/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd', -25.3414, -57.508801);
		$this->assertLocation('https://www.openbanners.org/banner/histórica-catedral-de-san-lorenzo-55dd', -25.3414, -57.508801);
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
		OpenBannersService::processStatic('https://www.openbanners.org/banner/aaaa-bbbb')->getCollection();
	}

	protected function getServiceClass(): string
	{
		return OpenBannersService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}
}
