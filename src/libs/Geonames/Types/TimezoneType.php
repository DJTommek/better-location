<?php declare(strict_types=1);

namespace App\Geonames\Types;

use App\Utils\Coordinates;
use Tracy\Debugger;

/**
 * Response from Timezone API
 * @link http://www.geonames.org/export/web-services.html
 */
class TimezoneType
{
	public float $lat;
	public float $lng;

	/** The local current time with local timezone */
	public \DateTimeImmutable $time;
	/** Sunset in local time and local timezone */
	public \DateTimeImmutable $sunset;
	/** Sunrise local time and local timezone */
	public \DateTimeImmutable $sunrise;

	/** ISO countrycode */
	public string $countryCode;
	/** Name (language can be set with param lang) */
	public string $countryName;

	/**
	 * Offset to GMT at 1. January (in hours)
	 * @deprecated
	 */
	public int|float $gmtOffset;

	/**
	 * The amount of time in hours to add to UTC to get standard time in this time zone.
	 * Because this value is not affected by daylight saving time, it is called raw offset.
	 */
	public int|float $rawOffset;

	/**
	 * Offset to GMT at 1. July (in hours)
	 * @deprecated
	 */
	public int|float $dstOffset;
	/** GMT or DST offset based if DST is active or not (in hours) */
	public float|int $nowOffset;

	/** Name of the timezone (according to Olson database), this information is sufficient to work with the timezone and defines DST rules */
	public string $timezoneId;
	public \DateTimeZone $timezone;

	public Coordinates $coords;

	public static function fromResponse(\stdClass $response): ?self
	{
		if (isset($response->timezoneId) === false) {
			Debugger::log(sprintf('TimezoneID is empty. Raw response: %s', json_encode($response)), Debugger::DEBUG);
			return null;
		}
		$result = new self();
		$result->timezone = new \DateTimeZone($response->timezoneId);

		foreach ((array)$response as $item => $value) {
			$result->{$item} = match($item) {
				'time' => new \DateTimeImmutable($response->time, $result->timezone),
				'sunrise' => new \DateTimeImmutable($response->sunrise, $result->timezone),
				'sunset' => new \DateTimeImmutable($response->sunset, $result->timezone),
				default => $value,
			};
		}

		$result->coords = new Coordinates($response->lat, $response->lng);
		$result->nowOffset = $result->isDst() ? $response->dstOffset : $response->gmtOffset;

		return $result;
	}

	public function formatRawOffset(): string
	{
		return $this->formatOffset($this->rawOffset);
	}

	/**
	 * Formatted offset to GMT at 1. January
	 * @deprecated
	 */
	public function formatGmtOffset(): string
	{
		return $this->formatOffset($this->gmtOffset);
	}

	/**
	 * Formatted offset to GMT at 1. July
	 * @deprecated
	 */
	public function formatDstOffset(): string
	{
		return $this->formatOffset($this->dstOffset);
	}

	public function formatNowOffset(): string
	{
		return $this->formatOffset($this->nowOffset);
	}

	private function formatOffset(float $value): string
	{
		$hoursAbs = abs($value);
		$minutes = floor((abs($value) - floor($hoursAbs)) * 60);
		$hours = floor($hoursAbs);

		return sprintf('%s%s:%s',
			$value >= 0 ? '+' : '-',
			str_pad((string)$hours, 2, '0', STR_PAD_LEFT),
			str_pad((string)$minutes, 2, '0', STR_PAD_LEFT),
		);
	}

	public function isDst(): bool
	{
		$now = $this->time->getTimestamp();
		$transitions = $this->timezone->getTransitions($now, $now);
		return $transitions[0]['isdst'];
	}
}
