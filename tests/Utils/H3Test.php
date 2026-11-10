<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\H3;
use PHPUnit\Framework\TestCase;

final class H3Test extends TestCase
{
	public static function isValidProvider(): array
	{
		return [
			[true, '8d1e3541e32aa7f'],
			[true, '8a1e3541e32ffff'],
			[true, '831e30fffffffff'],
			[true, '81cfbffffffffff'],
			[true, '8D1E3541E32AA7F'], // case-insensitive
			[true, '08d1e3541e32aa7f'], // leading zero (64-bit representation)
			[true, '8009fffffffffff'], // resolution 0, base cell 4 (pentagon)
			[true, '80f3fffffffffff'], // resolution 0, base cell 121 (last base cell)
			[true, '8f1e3541e32a51c'], // resolution 15
			[true, '81083ffffffffff'], // resolution 1, pentagon center child
			[true, '8108bffffffffff'], // resolution 1, pentagon child with digit 2
			[true, '811e7ffffffffff'], // resolution 1, hexagon child with digit 1

			[false, ''],
			[false, '8'],
			[false, 'abc'],
			[false, '8d1e3541e32aa7g'], // invalid character
			[false, '8d1e3541e32aa7f0'], // too long
			[false, '18d1e3541e32aa7f'], // mode bits
			[false, 'fd1e3541e32aa7f'], // mode bits
			[false, '81087ffffffffff'], // pentagon with deleted k-axes digit
			[false, '8108effffffffff'], // unused digits must be 7
			[false, '80f5fffffffffff'], // base cell 122 does not exist
			[false, '8d1e3541e32aa7'], // missing character
			[false, '9d1e3541e32aa7f'], // reserved bits
			[false, '8d1e3541e32aa7f '], // whitespace
		];
	}

	/**
	 * @dataProvider isValidProvider
	 */
	public function testIsValid(bool $expected, string $input): void
	{
		$this->assertSame($expected, H3::isValid($input));
	}

	/**
	 * Values from https://github.com/DJTommek/better-location/issues/144 (generated via https://h3geo.org/) and from
	 * official H3 documentation.
	 */
	public static function toCoordinatesProvider(): array
	{
		return [
			[50.087648021, 14.421216248, 13, '8d1e3541e32aa7f'],
			[50.087910736, 14.421251456, 10, '8a1e3541e32ffff'],
			[49.495205812, 14.102299669, 3, '831e30fffffffff'],
			[-48.104103209, -69.146022106, 1, '81cfbffffffffff'],
			[37.345793375, -121.976375973, 5, '85283473fffffff'],
			[64.700000128, 10.536199075, 0, '8009fffffffffff'], // pentagon
			[64.700000128, 10.536199075, 15, '8f0800000000000'], // pentagon
			[50.087448, 14.420676, 15, '8f1e3541e32a51c'],
		];
	}

	/**
	 * @dataProvider toCoordinatesProvider
	 */
	public function testToCoordinates(float $expectedLat, float $expectedLon, int $expectedResolution, string $code): void
	{
		$coords = H3::toCoordinates($code);
		$this->assertEqualsWithDelta($expectedLat, $coords->getLat(), 0.000_001);
		$this->assertEqualsWithDelta($expectedLon, $coords->getLon(), 0.000_001);
		$this->assertSame($expectedResolution, H3::getResolution($code));
	}

	public static function fromCoordinatesProvider(): array
	{
		return [
			['8d1e3541e32aa7f', 50.087648021, 14.421216248, 13],
			['8a1e3541e32ffff', 50.08791074, 14.42125146, 10],
			['831e30fffffffff', 49.495206, 14.102300, 3],
			['81cfbffffffffff', -48.10410, -69.14602, 1],
			['85283473fffffff', 37.3615593, -122.0553238, 5],
			['87283472bffffff', 37.3615593, -122.0553238, 7],
			['801ffffffffffff', 50.087451, 14.420671, 0],
			['8f1e3541e32a51c', 50.087451, 14.420671, 15],
			['8009fffffffffff', 64.7, 10.536199, 0], // pentagon
			['80f3fffffffffff', -90, 0, 0], // south pole
		];
	}

	/**
	 * @dataProvider fromCoordinatesProvider
	 */
	public function testFromCoordinates(string $expectedCode, float $lat, float $lon, int $resolution): void
	{
		$this->assertSame($expectedCode, H3::fromCoordinates($lat, $lon, $resolution));
	}

	/**
	 * Center of cell must be encoded into the same cell on every resolution.
	 */
	public function testRoundTrip(): void
	{
		$points = [
			[50.087451, 14.420671],
			[-33.856784, 151.215297],
			[40.689247, -74.044502],
			[-54.801912, -68.302951],
			[89.999, 45],
			[-89.999, -45],
			[0, 180],
			[0, -180],
			[64.7, 10.536199], // pentagon
		];

		foreach ($points as [$lat, $lon]) {
			for ($res = H3::MIN_RESOLUTION; $res <= H3::MAX_RESOLUTION; $res++) {
				$code = H3::fromCoordinates($lat, $lon, $res);
				$this->assertTrue(H3::isValid($code));
				$this->assertSame($res, H3::getResolution($code));

				$center = H3::toCoordinates($code);
				$this->assertSame($code, H3::fromCoordinates($center->getLat(), $center->getLon(), $res));
			}
		}
	}

	public function testAverageArea(): void
	{
		$this->assertEqualsWithDelta(4_357_449_416_078.39, H3::getAverageAreaM2(0), 0.01);
		$this->assertEqualsWithDelta(43.87, H3::getAverageAreaM2(13), 0.01);
		$this->assertEqualsWithDelta(0.895, H3::getAverageAreaM2(15), 0.001);
	}

	public function testInvalidResolution(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		H3::fromCoordinates(50.087451, 14.420671, 16);
	}

	public function testInvalidCodeToCoordinates(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		H3::toCoordinates('8d1e3541e32aa7g');
	}
}
