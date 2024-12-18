<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\MaidenheadLocator;
use DJTommek\Coordinates\Coordinates;
use PHPUnit\Framework\TestCase;
use Tests\IteratorTrait;
use Tests\TestUtils;

final class MaidenheadLocatorTest extends TestCase
{
	use IteratorTrait;

	public static function coordinatesProvider(): array
	{
		return [
			['JO70FC00LX', 50.087451, 14.420671],
			['RD47NK66AW', -52.554355, 169.133373],
			['BL11BH16OU', 21.32016, -157.90326],
			['BL11BH16IU', 21.32016, -157.90540],
			// Examples from https://www.giangrandi.org/electronics/radio/qthloccalc.shtml
			['JN58RJ', 48.396, 11.458, 2],
			['KF29OH', -30.688, 25.208, 2],
			['FE62ES', -47.229, -67.625, 2],
			['DN15GA', 45.021, -117.458, 2],
			// Examples from https://pypi.org/project/maidenhead/
			['BP65AA', 65.0, -148.0, 2],
			['BP65AA12', 65.0104, -147.9875, 3],
		];
	}

	public static function codesProvider(): array
	{
		return [
			['JO70FC00LX', 50.087413194444, 14.420671],
			['RD47NK66AW', -52.554427083333, 169.133373],
			['BL11BH16OU', 21.320225694444, -157.90326],
			['BL11BH16IU', 21.320225694444, -157.90540],
			// Examples from https://www.giangrandi.org/electronics/radio/qthloccalc.shtml
			['JN58RJ', 48.396, 11.458],
			['KF29OH', -30.688, 25.208],
			['FE62ES', -47.229, -67.625],
			['DN15GA', 45.021, -117.458],
			// Examples from https://pypi.org/project/maidenhead/
			['BP65AA', 65.021, -147.958],
			['BP65AA12', 65.0104, -147.9875],
		];
	}

	/**
	 * @dataProvider codesProvider
	 */
	public function testFromCode(string $code, float $expectedLat, float $expectedLon): void
	{
		$result = MaidenheadLocator::fromCode($code);
		$delta = pow(0.1, $result->getPrecision());
		$this->assertSame($code, $result->getCode());
		$this->assertEqualsWithDelta($expectedLat, $result->getLat(), $delta);
		$this->assertEqualsWithDelta($expectedLat, $result->getLat(), $delta);
	}

	/**
	 * @dataProvider coordinatesProvider
	 */
	public function testFromCoordinates(string $expectedCode, float $lat, float $lon, int $precision = 4): void
	{
		$result = MaidenheadLocator::fromCoordinates(new Coordinates($lat, $lon));
		$this->assertSame($expectedCode, $result->getCode($precision));
		$this->assertSame($lat, $result->getLat());
		$this->assertSame($lon, $result->getLon());
	}

	/**
	 * Generate random coordinate, convert them to code, this code convert back to coordinates and compare them with these randomly generated.
	 * Aaaaaand do it multiple time.
	 *
	 * @dataProvider iterator100Provider
	 */
	public function testRandom(): void
	{
		$lat = TestUtils::randomLat();
		$lon = TestUtils::randomLon();
		$result1 = MaidenheadLocator::fromCoordinates(new Coordinates($lat, $lon));
		$result2 = MaidenheadLocator::fromCode($result1->getCode());

		$this->assertTrue(MaidenheadLocator::isValid($result1->getCode()));
		$this->assertSame($result1->getCode(), $result2->getCode());

		$this->assertEqualsWithDelta($lat, $result2->getLat(), 0.00009);
		$this->assertEqualsWithDelta($lon, $result2->getLon(), 0.0009);
	}
}
