<?php declare(strict_types=1);

namespace Utils;

class DateImmutableUtils
{
	public static function fromTimestampMs($timestampMs, \DateTimeZone $timezone = null): \DateTimeImmutable {
		return self::fromTimestamp(intval($timestampMs / 1000), $timezone);
	}

	public static function fromTimestamp(int $timestamp, \DateTimeZone $timezone = null): \DateTimeImmutable {
		if (is_null($timezone)) {
			$timezone = new \DateTimeZone(date_default_timezone_get());
		}
		return (new \DateTimeImmutable())->setTimestamp($timestamp)->setTimezone($timezone);
	}
}
