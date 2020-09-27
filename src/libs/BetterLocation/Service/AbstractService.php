<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use Utils\General;

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

	public static function getConstants() {
		return [];
	}
}
