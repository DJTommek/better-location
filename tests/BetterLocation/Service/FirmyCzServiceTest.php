<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FirmyCzService;
use PHPUnit\Framework\TestCase;

final class FirmyCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		FirmyCzService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		FirmyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIfValidLinks(): void
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
	public function testValidLinks(): void
	{
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('https://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->getFirst()->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('https://www.firmy.cz/detail/13300341-blablabla')->getFirst()->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('https://www.firmy.cz/detail/13300341')->getFirst()->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('http://www.firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->getFirst()->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('https://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->getFirst()->__toString());
		$this->assertSame('49.364246,16.644386', FirmyCzService::processStatic('http://firmy.cz/detail/13300341-restaurace-a-pivnice-u-slunce-blansko.html')->getFirst()->__toString());
		$this->assertSame('49.541035,15.361975', FirmyCzService::processStatic('https://www.firmy.cz/detail/13134188-kosmeticky-salon-h2o-humpolec.html')->getFirst()->__toString());
		$this->assertSame('50.087414,14.469195', FirmyCzService::processStatic('https://www.firmy.cz/detail/13139938-zelva-beers-burgers-praha-zizkov.html')->getFirst()->__toString());
		$this->assertSame('50.221840,12.190701', FirmyCzService::processStatic('https://www.firmy.cz/detail/207772-penny-market-as.html')->getFirst()->__toString());
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
}
