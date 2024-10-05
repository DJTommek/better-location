<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\UTMService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;
use Tests\LocationTrait;

final class UTMServiceTest extends AbstractServiceTestCase
{
	use LocationTrait;

	protected function getServiceClass(): string
	{
		return UTMService::class;
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
			['33U 458556 5548514', 50.087451, 14.420671],
			['49R 712176 2967738', 26.815085, 113.134776],
			['2E 460078 3170913', -61.593128, -171.752183],
			[null, -86.744805, -44.77887], // Out of calculable range
		];
	}

	public static function basicProvider(): array
	{
		return [
			[50.087453, 14.420675, '33U 458557 5548515'],
			[50.087453, 14.420675, '33u 458557 5548515'],
			[43.642561, -79.387142, '17N 630084 4833438'],
			[-34.305675650139, 84.483499618096, '45H 268415 6201083'],
			[0.000001, 0.000001, '31N 166022 0'],
		];
	}

	public static function emptyInputProvider(): array
	{
		return [
			['Nothing valid'],
			'MGRS coordinates with spaces' => ['33U VR 1234 1234'],
			'MGRS coordinates without spaces' => ['33UVR12341234'],
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


	/**
	 * @dataProvider basicProvider
	 */
	public function testCoordinates(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new UTMService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon, delta: 0.000_01);
	}

	/**
	 * @dataProvider emptyInputProvider
	 */
	public function testEmpty(string $input): void
	{
		$service = new UTMService();
		$service->setInput($input);
		$this->assertFalse($service->validate());
	}

	/**
	 * @dataProvider textProvider
	 */
	public function testFindInText(array $expectedCoordsArray, string $text): void
	{
		$collection = UTMService::findInText($text);
		$this->assertSame(count($expectedCoordsArray), count($collection));

		foreach ($collection as $key => $betterLocation) {
			[$expectedLat, $expectedLon] = $expectedCoordsArray[$key];
			$this->assertCoordsWithDelta($expectedLat, $expectedLon, $betterLocation);
			$this->assertNull($betterLocation->getSourceType());
		}
	}

	/**
	 * @dataProvider generateShareTextProvider
	 */
	public function testGenerateShareText(?string $expected, float $lat, float $lon): void
	{
		$real = UTMService::getShareText($lat, $lon);

		$this->assertSame($expected, $real);
	}
}
