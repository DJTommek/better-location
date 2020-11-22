<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\ZanikleObceCzService;
use PHPUnit\Framework\TestCase;

final class ZanikleObceCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('http://zanikleobce.cz/index.php?menu=222&mpx=14.420671&mpy=50.087451', ZanikleObceCzService::getLink(50.087451, 14.420671));
		$this->assertEquals('http://zanikleobce.cz/index.php?menu=222&mpx=14.500000&mpy=50.100000', ZanikleObceCzService::getLink(50.1, 14.5));
		$this->assertEquals('http://zanikleobce.cz/index.php?menu=222&mpx=14.600000&mpy=-50.200000', ZanikleObceCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('http://zanikleobce.cz/index.php?menu=222&mpx=-14.700001&mpy=50.300000', ZanikleObceCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('http://zanikleobce.cz/index.php?menu=222&mpx=-14.800008&mpy=-50.400000', ZanikleObceCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		ZanikleObceCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidObec(): void
	{
		$this->assertTrue(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/?obec=26831'));
	}

	public function testIsValidDetail(): void
	{
		$this->assertTrue(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php?detail=1110015')); // valid but doesn't contain any location

		$this->assertTrue(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('http://zanikleOBCE.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('http://zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('http://zanikleobce.cz/index.php?lang=d&detail=282687')); // changed language to Deutsch

		$this->assertTrue(ZanikleObceCzService::isValid('https://www.zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('https://www.zanikleobce.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('https://zanikleobce.CZ/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('https://zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValid('https://ZANIKLEobce.cz/index.php?lang=d&detail=282687')); // changed language to Deutsch
		$this->assertTrue(ZanikleObceCzService::isValid('https://zanikleobce.cz/index.php?detail=282687&lang=d')); // changed language to Deutsch

		$this->assertFalse(ZanikleObceCzService::isValid('some invalid url'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php?detail='));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php?detail=-282687'));
		$this->assertFalse(ZanikleObceCzService::isValid('https://ZANIKLEobce.cz/index.php?detail=aaa'));
		$this->assertFalse(ZanikleObceCzService::isValid('https://www.zanikleobce.cz/index.php?detail=123aaa'));
		$this->assertFalse(ZanikleObceCzService::isValid('https://www.zanikleobce.CZ/index.php?detail=aaa123'));
		$this->assertFalse(ZanikleObceCzService::isValid('https://www.zanikleobce.cz/index.php?detail=aaa123aaa'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php?DETAIL=282687'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/?DETAIL=282687'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://zanikleobce.cz/'));
		$this->assertFalse(ZanikleObceCzService::isValid('http://www.zanikleobce.cz/index.php'));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testUrlObec(): void
	{
		$this->assertEquals('48.590270,14.234440', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?obec=502')->__toString());
		$this->assertEquals('49.786750,12.557330', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?obec=22307')->__toString());
		$this->assertEquals('48.915560,13.889190', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?obec=7087')->__toString());
		$this->assertEquals('50.111750,14.509370', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?obec=27819')->__toString());
		$this->assertEquals('50.519070,13.644160', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?lang=d&obec=27059')->__toString());
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testUrlDetail(): void
	{
		$this->assertEquals('48.590270,14.234440', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?detail=119532')->__toString());
		$this->assertEquals('48.915560,13.889190', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?detail=223422')->__toString());
		$this->assertEquals('48.915560,13.889190', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?detail=1451711')->__toString());
		$this->assertEquals('49.778330,13.120830', ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?lang=d&detail=48637')->__toString());
	}

	public function testMissingCoordinates(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Detail page "http://www.zanikleobce.cz/index.php?detail=1110015" has no location.');
		ZanikleObceCzService::parseCoords('http://www.zanikleobce.cz/index.php?detail=1110015');
	}
}
