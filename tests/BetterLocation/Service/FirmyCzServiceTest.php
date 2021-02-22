<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FirmyCzService;
use PHPUnit\Framework\TestCase;

final class FirmyCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link is not supported.');
		FirmyCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		FirmyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIfValidLinks(): void
	{
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/13300341-blablabla'));
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/13300341'));
		$this->assertTrue(FirmyCzService::isUrl('http://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isUrl('https://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isUrl('http://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/13134188-kosmeticky-salon-h2o-humpolec.html'));
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/13139938-zelva-beers-burgers-praha-zizkov.html'));
		$this->assertTrue(FirmyCzService::isUrl('https://www.firmy.cz/detail/207772-penny-market-as.html'));

		$this->assertFalse(FirmyCzService::isUrl('https://www.firma.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html'));
		$this->assertFalse(FirmyCzService::isUrl('http://firma.cz/detail/13300341-blablabla'));
		$this->assertFalse(FirmyCzService::isUrl('https://www.firmy.cz/aaa/13300341'));
		$this->assertFalse(FirmyCzService::isUrl('https://www.firmy.cz/detail/a13300341'));
	}

	public function testValidLinks(): void
	{
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('https://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('https://www.firmy.cz/detail/13300341-blablabla')->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('https://www.firmy.cz/detail/13300341')->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('http://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('https://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::parseUrl('http://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->__toString());
		$this->assertSame('49.541035,15.361974', FirmyCzService::parseUrl('https://www.firmy.cz/detail/13134188-kosmeticky-salon-h2o-humpolec.html')->__toString());
		$this->assertSame('50.087414,14.469195', FirmyCzService::parseUrl('https://www.firmy.cz/detail/13139938-zelva-beers-burgers-praha-zizkov.html')->__toString());
		$this->assertSame('50.221840,12.190701', FirmyCzService::parseUrl('https://www.firmy.cz/detail/207772-penny-market-as.html')->__toString());
	}

	public function testInvalid(): void
	{
		$this->expectException(\DJTommek\MapyCzApi\MapyCzApiException::class);
		$this->expectExceptionCode(404);
		$this->expectExceptionMessage('Not Found');
		$this->assertSame('50.077886,14.371990', FirmyCzService::parseUrl('https://www.firmy.cz/detail/9999999-restaurace-a-pivnice-u-slunce-blansko.html')->__toString());
	}
}
