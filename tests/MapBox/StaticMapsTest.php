<?php declare(strict_types=1);

namespace Tests\MapBox;

use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use PHPUnit\Framework\TestCase;

final class StaticMapsTest extends TestCase
{
	public static function basicProvider(): array
	{
		return [
			[
				'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/geojson(%7B%22type%22%3A%22Feature%22%2C%22properties%22%3A%7B%22marker-label%22%3A%2212%22%7D%2C%22geometry%22%3A%7B%22type%22%3A%22MultiPoint%22%2C%22coordinates%22%3A%5B%5B2.0%2C1.0%5D%5D%7D%7D)/auto/600x600?access_token=someApiKey',
				[new Coordinates(1, 2)],
			],
			[
				'https://api.mapbox.com/styles/v1/mapbox/streets-v12/static/geojson(%7B%22type%22%3A%22Feature%22%2C%22properties%22%3A%7B%22marker-label%22%3A%2212%22%7D%2C%22geometry%22%3A%7B%22type%22%3A%22MultiPoint%22%2C%22coordinates%22%3A%5B%5B2.0%2C1.0%5D%2C%5B86.987654%2C-9.12345%5D%5D%7D%7D)/auto/600x600?access_token=someApiKey',
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
		$api = new \App\MapBox\StaticMaps('someApiKey');
		$privateUrl = $api->generatePrivateUrl($markers);
		$this->assertSame($expectedPrivateUrl, $privateUrl);
	}
}
