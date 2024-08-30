<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\WGS84DegreesSecondsService;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class WGS84DegreesSecondsServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return WGS84DegreesSecondsService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function basicProvider(): array
	{
		return [
			[49.384934, 13.713827, '49°1352.881"N, 13°2514.888"E'],

			[47.352537222222, 11.71382666667, '47°1234.567"N, 11°2514.888"E'],
			[47.352537222222, 11.71382666667, '47°1234.567"N 11°2514.888"E'],
			[47.352537222222, 11.71382666667, '47°1234.567", 11°2514.888"'],
			[-47.352537222222, 11.71382666667, '47°1234.567" S, 11°2514.888"E'],
			[47.352537222222, -11.71382666667, '47°1234.567"N, 11°2514.888" W'],
			[-47.352537222222, -11.71382666667, '47°1234.567"S, 11°2514.888"W'],
			[11.71382666667, 47.352537222222, '47°1234.567"E, 11°2514.888"N'], // Lon Lat
			[-11.71382666667, -47.352537222222, '47°1234.567"W, 11°2514.888"S'], // Lon Lat
			[-4.3525372222222, -1.713826666670, '-4°1234.567", -1°2514.888"'],
			[87.352537222222, 128.713826666670, '87°1234.567"N, 128°2514.888"E'],
		];
	}

	public static function emptyProvider(): array
	{
		return [
			['Nothing valid'],

			['48.123456,13.553366'], // valid D but not DS
			['N 48.123456°, E 13.553366°'], // valid D but not DS

			['48°7.407,13°33.202'], // valid DM but not DS
			['N 48° 7.40736\', E 13° 33.20196\''], // valid DM but not DS

			['48°7\'24.442,13°33\'12.118'], // valid DMS but not DS
			['N 48° 7\' 24.442", E 13° 33\' 12.118"'], // valid DMS but not DS
		];
	}

	/**
	 * @dataProvider basicProvider
	 */
	public function testCoordinates(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WGS84DegreesSecondsService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}

	/**
	 * @dataProvider emptyProvider
	 */
	public function testEmpty(string $input): void
	{
		$service = new WGS84DegreesSecondsService();
		$service->setInput($input);
		$this->assertFalse($service->validate());
	}
}
