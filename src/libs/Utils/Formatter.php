<?php declare(strict_types=1);

namespace App\Utils;

class Formatter
{
	private const int MINUTES = 60;
	private const int HOURS = 3600;
	private const int DAYS = 86400;
	private const int MONTHS = 2592000;
	private const int YEARS = 31536000;

	/**
	 * Format seconds into human readable format. To format higher numbers (months and higher) {@see self::secondsShort}.
	 *
	 * @param float|int $input In seconds. If float with non-zero decimal it might return milliseconds too.
	 * @param bool $short Set to true to return only the largest part of formatted number.
	 * @return string Human readable formatted string (16d 1h 3m 23s)
	 * @example 1386203 -> 16d 1h 3m 23s
	 * @example 1386203, true -> 16d
	 */
	public static function seconds(float|int $input, bool $short = false): string
	{
		if ($input < 0) {
			throw new \InvalidArgumentException('Input must be higher or equal zero.');
		}
		if ($input === 0 || $input === 0.0) {
			return '0s';
		}

		$totalSeconds = $input;

		$milliseconds = (int)($totalSeconds * 1000) % 1000;
		$seconds = (int)$totalSeconds % 60;
		$minutes = (int)($totalSeconds / 60) % 60;
		$hours = (int)($totalSeconds / (60 * 60)) % 24;
		$days = (int)($totalSeconds / (60 * 60 * 24));

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
		if ($milliseconds > 0) {
			$parts[] = $milliseconds . 'ms';
		}

		if ($parts === []) {
			return '0ms'; // Input is in float with three zeros in decimal place (0.000xxxxx)
		}

		if ($short) {
			return reset($parts);
		} else {
			return join(' ', $parts);
		}
	}

	/**
	 * Format seconds into human readable format
	 *
	 * @param float|int $input In seconds. If float with non-zero decimal it might return milliseconds too.
	 * @return string Human readable formatted string
	 * @example 65 -> 1 minute
	 * @example 1386203 -> 16 days
	 * @example 63072000 -> 2 years
	 */
	public static function secondsShort(float|int $input): string
	{
		if ($input < 0) {
			throw new \InvalidArgumentException('Input must be higher or equal zero.');
		}
		if ($input < 1) {
			return '0 seconds';
		}

		if ($input >= self::YEARS) {
			$value = floor($input / self::YEARS);
			$unit = 'year';
		} else if ($input >= self::MONTHS) {
			$value = floor($input / self::MONTHS);
			$unit = 'month';
		} else if ($input >= self::DAYS) {
			$value = floor($input / self::DAYS);
			$unit = 'day';
		} else if ($input >= self::HOURS) {
			$value = floor($input / self::HOURS);
			$unit = 'hour';
		} else if ($input >= self::MINUTES) {
			$value = floor($input / self::MINUTES);
			$unit = 'minute';
		} else {
			$value = floor($input);
			$unit = 'second';
		}

		return $value . ' ' . $unit . ($value > 1 ? 's' : '');
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

	/*
	 * Edit given size in bytes to human-read
	 * @author http://stackoverflow.com/a/5502088/3334403
	 */
	public static function size(int $bytes): string
	{
		$units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
		$power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
		return number_format($bytes / pow(1024, $power), 2, '.', ' ') . ' ' . $units[$power];
	}

	/**
	 * Build HTML tag <a>
	 *
	 * @TODO escape special characters
	 *
	 * @param string $link Target URL (required)
	 * @param string|null $text Visible text (null to use $link)
	 * @param string|false|null $title Title text attribute (null to use $text, false to not use at all)
	 * @param string|false|null $target Click target attribute (null to use "_blank", false to not use at all)
	 */
	public static function htmlLink(
		string $link,
		string|null $text = null,
		string|null|false $title = false,
		string|null|false $target = false,
	): string {
		$text = $text ?? $link;

		$result = sprintf('<a href="%s"', $link);

		if ($title === null) {
			$title = $text;
		}
		if ($title !== false) {
			$result .= sprintf(' title="%s"', $title);
		}

		if ($target === null) {
			$target = '_blank';
		}
		if ($target !== false) {
			$result .= sprintf(' target="%s"', $target);
		}

		$result .= sprintf('>%s</a>', $text);
		return $result;
	}
}
