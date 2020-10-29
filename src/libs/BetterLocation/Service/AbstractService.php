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
}
