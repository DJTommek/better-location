<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;

abstract class AbstractService
{
	abstract public static function getLink(float $lat, float $lon, bool $drive = false);

	abstract public static function isValid(string $input);

	abstract public static function parseCoords(string $input): BetterLocation;

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 */
	abstract public static function parseCoordsMultiple(string $input): BetterLocationCollection;

	public static function getConstants()
	{
		return [];
	}

	public static function getName(bool $short = false) {
		if ($short && defined(sprintf('%s::%s',static::class, 'NAME_SHORT'))) {
			return static::NAME_SHORT;
		} else {
			return static::NAME;
		}
	}
}
