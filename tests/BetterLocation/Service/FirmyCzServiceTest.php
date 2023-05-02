<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\FirmyCzService;

final class FirmyCzServiceTest extends AbstractServiceTestCase
{

	protected function getServiceClass(): string
	{
		return FirmyCzService::class;
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
	 * @group request
	 */
	public function testInvalid(): void
	{
		$this->expectException(\DJTommek\MapyCzApi\MapyCzApiException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Not Found');
		$this->assertSame('50.077886,14.371990', FirmyCzService::processStatic('https://www.firmy.cz/detail/9999999-restaurace-a-pivnice-u-slunce-blansko.html')->getFirst()->__toString());
	}

	public function testIsValid(): void
	{
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/13300341-blablabla'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/13300341'));
		$this->assertTrue(FirmyCzService::isValidStatic('http://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('http://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/13134188-kosmeticky-salon-h2o-humpolec.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/13139938-zelva-beers-burgers-praha-zizkov.html'));
		$this->assertTrue(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/207772-penny-market-as.html'));

		$this->assertFalse(FirmyCzService::isValidStatic('https://www.firma.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertFalse(FirmyCzService::isValidStatic('http://firma.cz/detail/13300341-blablabla'));
		$this->assertFalse(FirmyCzService::isValidStatic('https://www.firmy.cz/aaa/13300341'));
		$this->assertFalse(FirmyCzService::isValidStatic('https://www.firmy.cz/detail/a13300341'));
	}

	/**
	 * @group request
	 */
	public function testProcess(): void
	{
		$delta = 0.000_01;
		$this->assertLocation('https://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html', 49.364247, 16.644386, delta: $delta);
		$this->assertLocation('https://www.firmy.cz/detail/13300341-blablabla', 49.364246, 16.644386, delta: $delta);
		$this->assertLocation('https://www.firmy.cz/detail/13300341', 49.364246, 16.644386, delta: $delta);
		$this->assertLocation('http://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html', 49.364246, 16.644386, delta: $delta);
		$this->assertLocation('https://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html', 49.364246, 16.644386, delta: $delta);
		$this->assertLocation('http://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html', 49.364246, 16.644386, delta: $delta);

		$this->assertLocation('https://www.firmy.cz/detail/13134188-kosmeticky-salon-h2o-humpolec.html', 49.541035, 15.361975, delta: $delta);
		$this->assertLocation('https://www.firmy.cz/detail/13139938-zelva-beers-burgers-praha-zizkov.html', 50.087414, 14.469195, delta: $delta);
		$this->assertLocation('https://www.firmy.cz/detail/207772-penny-market-as.html', 50.221840, 12.190701, delta: $delta);
	}
}
