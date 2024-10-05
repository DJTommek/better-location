<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\MGRSService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class MGRSServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return MGRSService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function generateShareTextProvider(): array
	{
		return [
			['33UVR5855748515', 50.087451, 14.420671],
			['49RGK1217767738', 26.815085, 113.134776],
			['2EMS6007970914', -61.593128, -171.752183],
			[null, -86.744805, -44.77887], // Out of calculable range
		];
	}

	public static function emptyInputProvider(): array
	{
		return [
			['Nothing valid'],
			'UTM with spaces' => ['33U 458557 5548515'],
			'UTM without spaces' => ['33U4585575548515'],
		];
	}

	public static function textProvider(): array
	{
		return [
			[[], 'Nothing valid is here'],
			[[[50.087453, 14.420675]], 'Hi there, do you know this? 33U 458557 5548515 This is coordinate in UTM system.'],
			[
				[[50.087453, 14.420675], [43.642561, -79.387142], [-34.305675650139, 84.483499618096]],
				'Location 1: 33U 458557 5548515, Location 2: 17N 630084 4833438 and third is here too 45H 268415 6201083',
			],
			[[[49.123244, 15.555552]], 'Without spaces it works too 33U5405345441305, see?'],
			[[[43.642561, -79.387142]], 'Lowercased zone letter like this 17n 630084 4833438 is supported too'],
			[[], 'Not enough numbers in easting 17n 12 1234567'],
			[[], 'This is valid coordinate, but not enough numbers in northing 17n 123456 48'],
			[[], 'These are valid MGRS coordinates 33UVR12341234 which should not be validated as UTM coordinates'],
			[[], 'These are valid MGRS coordinates with spaces 33U VR 1234 1234 which should not be validated as UTM coordinates'],
		];
	}

	public function testValidLocation(): void
	{
		$this->assertSame('50.086359,14.408709', MGRSService::processStatic('33UVR577484')->getFirst()->__toString()); // Prague
		$this->assertSame('50.086359,14.408709', MGRSService::processStatic('33U VR 577 484')->getFirst()->__toString()); // Prague (with spaces)
		$this->assertSame('21.309433,-157.916867', MGRSService::processStatic('4QFJ12345678')->getFirst()->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertSame('21.309433,-157.916867', MGRSService::processStatic('04QFJ12345678')->getFirst()->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertSame('38.959391,-95.265482', MGRSService::processStatic('15SUD0370514711')->getFirst()->__toString());
		$this->assertSame('38.889801,-77.036543', MGRSService::processStatic('18SUJ2337106519')->getFirst()->__toString());
		$this->assertSame('60.775935,4.693467', MGRSService::processStatic('31VEH92233902')->getFirst()->__toString()); // Edge of Norway
		$this->assertSame('-34.051387,18.462069', MGRSService::processStatic('34HBH65742924')->getFirst()->__toString()); // South Africa
		$this->assertSame('-45.892917,170.503103', MGRSService::processStatic('59GMK61451773')->getFirst()->__toString()); // New Zeland
		$this->assertSame('-45.892917,170.503103', MGRSService::processStatic('59G MK 6145 1773')->getFirst()->__toString()); // New Zeland (with spaces)
		// examples from https://www.usna.edu/Users/oceano/pguth/md_help/html/mgrs_utm.htm
		$this->assertSame('38.977083,-76.491277', MGRSService::processStatic('18SUJ7082315291')->getFirst()->__toString());
		$this->assertSame('38.977073,-76.491311', MGRSService::processStatic('18SUJ70821529')->getFirst()->__toString());
		$this->assertSame('38.976260,-76.491525', MGRSService::processStatic('18SUJ708152')->getFirst()->__toString());
	}

	/**
	 * @dataProvider emptyInputProvider
	 */
	public function testEmpty(string $input): void
	{
		$service = new MGRSService();
		$service->setInput($input);
		$this->assertFalse($service->validate());
	}

	/**
	 * @dataProvider generateShareTextProvider
	 */
	public function testGenerateShareText(?string $expected, float $lat, float $lon): void
	{
		$real = MGRSService::getShareText($lat, $lon);

		$this->assertSame($expected, $real);
	}
}
