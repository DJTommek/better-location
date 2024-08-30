<?php declare(strict_types=1);

namespace Tests\BingMaps;

use App\BingMaps\StaticMaps;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use PHPUnit\Framework\TestCase;

final class StaticMapsTest extends TestCase
{
	public static function basicProvider(): array
	{
		$prefix = 'https://dev.virtualearth.net/REST/V1/Imagery/Map/Road?key=someApiKey&mapSize=600%2C600';

		return [
			[
				$prefix . '&pp=1.000000,2.000000;1;1',
				[new Coordinates(1, 2)],
			],
			[
				$prefix . '&pp=1.000000,2.000000;1;1&pp=-9.123450,86.987654;1;2',
				[new Coordinates(1, 2), new Coordinates(-9.12345, 86.987654321)],
			],
		];
	}

	/**
	 * @dataProvider basicProvider
	 * @param array<CoordinatesInterface> $markers
	 */
	public function testBasic(string $expectedPrivateUrl, array $markers): void
	{
		$api = new StaticMaps('someApiKey');
		$privateUrl = $api->generatePrivateUrl($markers);
		$this->assertSame($expectedPrivateUrl, $privateUrl);
	}
}
