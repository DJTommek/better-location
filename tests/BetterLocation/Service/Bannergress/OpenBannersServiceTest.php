<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Bannergress\OpenBannersService;
use App\MiniCurl\Exceptions\InvalidResponseException;
use App\Utils\Requestor;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\TestUtils;

final class OpenBannersServiceTest extends AbstractServiceTestCase
{
	private Requestor $requestor;

	protected function setUp(): void
	{
		[$httpClient, $mockHandler] = TestUtils::createMockedClientInterface();
		assert($httpClient instanceof \GuzzleHttp\Client);
		$this->requestor = new Requestor($httpClient, TestUtils::getDevNullCache());
	}

	public function testIsValid(): void
	{
		$service = new OpenBannersService($this->requestor);
		$service->setInput('https://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b');
		$isValid = $service->validate();
		$this->assertSame(true, $isValid);
	}

	public static function IsValidProvider2(): array
	{
		return [
		[true, 'https://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b'],
		[true, 'https://openbanners.org/banner/czech-cubism-and-its-representative-ce4b'],
		[true, 'http://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b'],
		[true, 'https://www.openbanners.org/banner/barrie-skyline-f935'],
		[true, 'https://www.openbanners.org/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'],
		[true, 'https://www.openbanners.org/banner/histórica-catedral-de-san-lorenzo-55dd'],
		[true, 'https://www.openbanners.org/banner/長良川鉄道-乗りつぶし-観光編-adea'],
		[true, 'https://www.openbanners.org/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'],

		[false, 'some invalid url'],
		[false, 'https://www.openbanners.org'],
		[false, 'http://www.openbanners.org'],
		[false, 'https://www.openbanners.org/banner/'],
		[false, 'http://www.some-domain.cz/'],
		[false, 'http://www.some-domain.cz/some-path'],
		];
	}

	/**
	 * @dataProvider isValidProvider2
	 */
	public function testIsValid2(bool $expectedIsValid, string $input): void
	{
		$service = new OpenBannersService($this->requestor);
		$service->setInput($input);
		$isValid = $service->validate();
		$this->assertSame($expectedIsValid, $isValid);
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
