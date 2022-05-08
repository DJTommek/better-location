<?php declare(strict_types=1);

namespace App\Utils;

class Formatter
{
	/**
	 * Format distance to be human readable.
	 * @TODO add support for imperial units
	 *
	 * @param float $input Distance in meters
	 */
	public static function distance(float $input): string
	{
		if ($input >= 100_000) { // 100+ kilometers
			return sprintf('%d km', round($input / 1000));
		} else if ($input >= 10_000) { // 10 - 100 kilometers
			return sprintf('%.1F km', $input / 1000);
		} else if ($input >= 1_000) { // 1 - 10 kilometers
			return sprintf('%.2F km', $input / 1000);
		} else if ($input >= 10) { // 10 meters - 1 kilometer
			return sprintf('%d m', $input);
		} else if ($input >= 1) { // 1.x - 10 meters
			return sprintf('%.1F m', $input);
		} else if ($input >= 0) { // 0 - 1 meter
			return '< 1 m';
		} else { // 1 meter
			throw new \InvalidArgumentException('Distance must be higher than zero.');
		}
	}
}
