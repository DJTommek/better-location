<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\ZanikleObceCzService;

final class ZanikleObceCzServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return ZanikleObceCzService::class;
	}

	protected function getShareLinks(): array
	{
		$this->revalidateGeneratedShareLink = false;

		return [
			'http://zanikleobce.cz/index.php?menu=222&mpx=14.420671&mpy=50.087451',
			'http://zanikleobce.cz/index.php?menu=222&mpx=14.500000&mpy=50.100000',
			'http://zanikleobce.cz/index.php?menu=222&mpx=14.600000&mpy=-50.200000', // round down
			'http://zanikleobce.cz/index.php?menu=222&mpx=-14.700001&mpy=50.300000', // round up
			'http://zanikleobce.cz/index.php?menu=222&mpx=-14.800008&mpy=-50.400000',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}


	public function isValidObecProvider(): array
	{
		return [
			[true, 'http://www.zanikleobce.cz/?obec=26831'],
			[true, 'http://www.zanikleobce.cz/?obec=1'],
			[true, 'http://www.zanikleobce.cz/?obec=999999'],

			[false, 'http://www.zanikleobce.cz/?obec='],
			[false, 'http://www.zanikleobce.cz/?obec=0'],
			[false, 'http://www.zanikleobce.cz/?obec=aaa'],
			[false, 'http://www.zanikleobce.cz/?obec=-26831'],
			[false, 'http://www.zanikleobce.cz/?obec=26831aaa'],
			[false, 'http://www.zanikleobce.cz/?obec=aaa26831'],
		];
	}

	public function isValidDetailProvider(): array
	{
		return [
			[true, 'http://www.zanikleobce.cz/index.php?detail=1110015'], // valid but doesn't contain any location

			[true, 'http://www.zanikleobce.cz/index.php?detail=282687'],
			[true, 'http://www.zanikleobce.cz/?detail=282687'],
			[true, 'http://zanikleOBCE.cz/?detail=282687'],
			[true, 'http://zanikleobce.cz/index.php?detail=282687'],
			[true, 'http://zanikleobce.cz/index.php?lang=d&detail=282687'], // changed language to Deutsch

			[true, 'https://www.zanikleobce.cz/index.php?detail=282687'],
			[true, 'https://www.zanikleobce.cz/?detail=282687'],
			[true, 'https://zanikleobce.CZ/?detail=282687'],
			[true, 'https://zanikleobce.cz/index.php?detail=282687'],
			[true, 'https://ZANIKLEobce.cz/index.php?lang=d&detail=282687'], // changed language to Deutsch
			[true, 'https://zanikleobce.cz/index.php?detail=282687&lang=d'], // changed language to Deutsch

			[false, 'some invalid url'],
			[false, 'http://www.zanikleobce.cz/index.php?detail='],
			[false, 'http://www.zanikleobce.cz/index.php?detail=-282687'],
			[false, 'https://ZANIKLEobce.cz/index.php?detail=aaa'],
			[false, 'https://www.zanikleobce.cz/index.php?detail=123aaa'],
			[false, 'https://www.zanikleobce.CZ/index.php?detail=aaa123'],
			[false, 'https://www.zanikleobce.cz/index.php?detail=aaa123aaa'],
			[false, 'http://www.zanikleobce.cz/index.php?DETAIL=282687'],
			[false, 'http://www.zanikleobce.cz/?DETAIL=282687'],
			[false, 'http://www.zanikleobce.cz/'],
			[false, 'http://zanikleobce.cz/'],
			[false, 'http://www.zanikleobce.cz/index.php'],
		];
	}

	public function processtUrlObecProvider(): array
	{
		return [
			[48.590270, 14.234440, 'http://www.zanikleobce.cz/index.php?obec=502'],
			[49.786750, 12.557330, 'http://www.zanikleobce.cz/index.php?obec=22307'],
			[48.915560, 13.889190, 'http://www.zanikleobce.cz/index.php?obec=7087'],
			[50.111750, 14.509370, 'http://www.zanikleobce.cz/index.php?obec=27819'],
			[50.519070, 13.644160, 'http://www.zanikleobce.cz/index.php?lang=d&obec=27059'],
		];
	}

	public function processUrlDetailProvider(): array
	{
		return [
			[48.590270, 14.234440, 'http://www.zanikleobce.cz/index.php?detail=119532'],
			[48.915560, 13.889190, 'http://www.zanikleobce.cz/index.php?detail=223422'],
			[48.915560, 13.889190, 'http://www.zanikleobce.cz/index.php?detail=1451711'],
			[49.778330, 13.120830, 'http://www.zanikleobce.cz/index.php?lang=d&detail=48637'],
		];
	}

	/**
	 * @dataProvider isValidObecProvider
	 * @dataProvider isValidDetailProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new ZanikleObceCzService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 * @dataProvider processtUrlObecProvider
	 * @dataProvider processUrlDetailProvider
	 */
	public function testProcess(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new ZanikleObceCzService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
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
