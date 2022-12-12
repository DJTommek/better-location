<?php declare(strict_types=1);

namespace App\Utils;

class Formatter
{
	/**
	 * Format seconds into human readable format
	 *
	 * @return string Human readable formatted string (16d 1h 3m 23s)
	 * @example 1386203 -> 16d 1h 3m 23s
	 */
	public static function seconds(int $input, bool $short = false): string
	{
		if ($input < 0) {
			throw new \InvalidArgumentException('Input must be higher or equal zero.');
		} else if ($input === 0) {
			return '0s';
		}

		$seconds = $input % 60;
		$minutes = (int)($input / 60) % 60;
		$hours = (int)($input / (60 * 60)) % 24;
		$days = (int)($input / (60 * 60 * 24));

		$parts = [];
		if ($days > 0) {
			$parts[] = $days . 'd';
		}
		if ($hours > 0) {
			$parts[] = $hours . 'h';
		}
		if ($minutes > 0) {
			$parts[] = $minutes . 'm';
		}
		if ($seconds > 0) {
			$parts[] = $seconds . 's';
		}

		if ($short) {
			return reset($parts);
		} else {
			return join(' ', $parts);
		}
	}

	/**
	 * Calculate number of seconds between provided date and now, then format it to human readable.
	 *
	 * @see self::seconds()
	 */
	public static function ago(\DateTimeInterface $input, bool $short = false): string
	{
		$diffAgo = time() - $input->getTimestamp();
		if ($diffAgo < 0) {
			throw new \InvalidArgumentException('Date must not be in the past.');
		}
		return self::seconds($diffAgo, $short);
	}

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
			throw new \InvalidArgumentException('Distance must be higher or equal zero.');
		}
	}
}
