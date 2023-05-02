<?php declare(strict_types=1);

namespace Tests;

use DJTommek\Coordinates\Coordinates;

final class TestUtils
{
	public static function randomLat(): float
	{
		return rand(-89_999_999, 89_999_999) / 1_000_000;
	}

	public static function randomLon(): float
	{
		return rand(-179_999_999, 179_999_999) / 1_000_000;
	}

	public static function randomCoords(): Coordinates
	{
		return new Coordinates(
			self::randomLat(),
			self::randomLon(),
		);
	}
}
