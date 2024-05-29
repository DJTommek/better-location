<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Bannergress;

use App\BetterLocation\Service\Bannergress\OpenBannersService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\HttpTestClients;

final class OpenBannersServiceTest extends AbstractServiceTestCase
{
	private readonly HttpTestClients $httpTestClients;

	protected function setUp(): void
	{
		parent::setUp();

		$this->httpTestClients = new HttpTestClients();
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

	public static function IsValidProvider(): array
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

	public static function processProvider(): array
	{
		return [
			[50.087213, 14.425674, 'https://www.openbanners.org/banner/czech-cubism-and-its-representative-ce4b'],
			[35.445393, 137.019408, 'https://www.openbanners.org/banner/長良川鉄道-乗りつぶし-観光編-adea'],
			[35.445393, 137.019408, 'https://www.openbanners.org/banner/%E9%95%B7%E8%89%AF%E5%B7%9D%E9%89%84%E9%81%93-%E4%B9%97%E3%82%8A%E3%81%A4%E3%81%B6%E3%81%97-%E8%A6%B3%E5%85%89%E7%B7%A8-adea'],
			[-25.3414, -57.508801, 'https://www.openbanners.org/banner/histórica-catedral-de-san-lorenzo-55dd'],
			[-25.3414, -57.508801, 'https://www.openbanners.org/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'],
			[-41.287008, 174.778374, 'https://www.openbanners.org/banner/a-visit-to-te-papa-dffa'],
			[-25.3414, -57.508801, 'https://openbanners.org/banner/hist%C3%B3rica-catedral-de-san-lorenzo-55dd'],
			[-25.3414, -57.508801, 'https://www.openbanners.org/banner/histórica-catedral-de-san-lorenzo-55dd'],
		];
	}

	/**
	 * @dataProvider IsValidProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new OpenBannersService($this->httpTestClients->realRequestor);
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
		$service = new OpenBannersService($this->httpTestClients->realRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @group requestOffline
	 * @dataProvider processProvider
	 */
	public function testProcessOffline(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new OpenBannersService($this->httpTestClients->offlineRequestor);
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * Pages, that do not have any location
	 *
	 * @group request
	 */
	public function testInvalidReal(): void
	{
		$service = new OpenBannersService($this->httpTestClients->realRequestor);
		$service->setInput('https://www.openbanners.org/banner/aaaa-bbbb');
		$this->assertTrue($service->validate());
		$service->process();
		$this->assertCount(0, $service->getCollection());
	}
}
