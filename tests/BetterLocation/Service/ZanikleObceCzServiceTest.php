<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\ZanikleObceCzService;
use PHPUnit\Framework\TestCase;

final class ZanikleObceCzServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('http://zanikleobce.cz/index.php?menu=222&mpx=14.420671&mpy=50.087451', ZanikleObceCzService::getLink(50.087451, 14.420671));
		$this->assertSame('http://zanikleobce.cz/index.php?menu=222&mpx=14.500000&mpy=50.100000', ZanikleObceCzService::getLink(50.1, 14.5));
		$this->assertSame('http://zanikleobce.cz/index.php?menu=222&mpx=14.600000&mpy=-50.200000', ZanikleObceCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('http://zanikleobce.cz/index.php?menu=222&mpx=-14.700001&mpy=50.300000', ZanikleObceCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('http://zanikleobce.cz/index.php?menu=222&mpx=-14.800008&mpy=-50.400000', ZanikleObceCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		ZanikleObceCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidObec(): void
	{
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=26831'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=1'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=999999'));

		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec='));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=0'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=aaa'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=-26831'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=26831aaa'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?obec=aaa26831'));
}

	public function testIsValidDetail(): void
	{
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php?detail=1110015')); // valid but doesn't contain any location

		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://zanikleOBCE.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('http://zanikleobce.cz/index.php?lang=d&detail=282687')); // changed language to Deutsch

		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://www.zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://www.zanikleobce.cz/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://zanikleobce.CZ/?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://zanikleobce.cz/index.php?detail=282687'));
		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://ZANIKLEobce.cz/index.php?lang=d&detail=282687')); // changed language to Deutsch
		$this->assertTrue(ZanikleObceCzService::isValidStatic('https://zanikleobce.cz/index.php?detail=282687&lang=d')); // changed language to Deutsch

		$this->assertFalse(ZanikleObceCzService::isValidStatic('some invalid url'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php?detail='));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php?detail=-282687'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('https://ZANIKLEobce.cz/index.php?detail=aaa'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('https://www.zanikleobce.cz/index.php?detail=123aaa'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('https://www.zanikleobce.CZ/index.php?detail=aaa123'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('https://www.zanikleobce.cz/index.php?detail=aaa123aaa'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php?DETAIL=282687'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/?DETAIL=282687'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://zanikleobce.cz/'));
		$this->assertFalse(ZanikleObceCzService::isValidStatic('http://www.zanikleobce.cz/index.php'));
	}

	/**
	 * @group request
	 */
	public function testUrlObec(): void
	{
		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?obec=502')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.590270,14.234440', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?obec=22307')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.786750,12.557330', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?obec=7087')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.915560,13.889190', $collection[0]->__toString());

		$collection =ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?obec=27819')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.111750,14.509370', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?lang=d&obec=27059')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.519070,13.644160', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testUrlDetail(): void
	{
		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?detail=119532')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.590270,14.234440', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?detail=223422')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.915560,13.889190', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?detail=1451711')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('48.915560,13.889190', $collection[0]->__toString());

		$collection = ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?lang=d&detail=48637')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('49.778330,13.120830', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinates(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Detail page "http://www.zanikleobce.cz/index.php?detail=1110015" has no location.');
		ZanikleObceCzService::processStatic('http://www.zanikleobce.cz/index.php?detail=1110015');
	}
}
