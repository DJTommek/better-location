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
	/** @var float */
	public $lat;
	/** @var float */
	public $lng;

	/** @var \DateTimeImmutable the local current time with local timezone */
	public $time;
	/** @var \DateTimeImmutable sunset in local time and local timezone */
	public $sunset;
	/** @var \DateTimeImmutable sunrise local time and local timezone */
	public $sunrise;

	/** @var string ISO countrycode */
	public $countryCode;
	/** @var string name (language can be set with param lang) */
	public $countryName;

	/**
	 * @var int|float offset to GMT at 1. January
	 * @deprecated
	 */
	public $gmtOffset;
	/**
	 * @var int|float the amount of time in hours to add to UTC to get standard time in this time zone.
	 * Because this value is not affected by daylight saving time, it is called raw offset.
	 */
	public $rawOffset;
	/**
	 * @var int|float offset to GMT at 1. July
	 * @deprecated
	 */
	public $dstOffset;
	/**
	 * @var int|float GMT or DST offset based if DST is active or not
	 */
	public $nowOffset;

	/** @var string name of the timezone (according to Olson database), this information is sufficient to work with the timezone and defines DST rules */
	public $timezoneId;
	/** @var \DateTimeZone created from $timezoneId */
	public $timezone;

	/** @var Coordinates Generated from lat and lon */
	public $coords;

	public static function fromResponse(\stdClass $response): ?self
	{
		if (isset($response->timezoneId) === false) {
			Debugger::log(sprintf('TimezoneID is empty. Raw response: %s', json_encode($response)), Debugger::DEBUG);
			return null;
		}
		$result = new self();
		foreach ($response as $item => $value) {
			$result->{$item} = $value;
		}

		$result->timezone = new \DateTimeZone($result->timezoneId);

		$result->coords = new Coordinates($result->lat, $result->lng);
		$result->time = new \DateTimeImmutable($result->time, $result->timezone);
		$result->sunset = new \DateTimeImmutable($result->sunset, $result->timezone);
		$result->sunrise = new \DateTimeImmutable($result->sunrise, $result->timezone);
		$result->nowOffset = $result->isDst() ? $result->dstOffset : $result->gmtOffset;

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
