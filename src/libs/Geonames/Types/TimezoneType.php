<?php declare(strict_types=1);

namespace App\Geonames\Types;

use App\Utils\Coordinates;

class TimezoneType
{
	/** @var float */
	public $lat;
	/** @var float */
	public $lng;

	/** @var \DateTimeImmutable */
	public $time;
	/** @var \DateTimeImmutable */
	public $sunset;
	/** @var \DateTimeImmutable */
	public $sunrise;

	/** @var string */
	public $countryCode;
	/** @var string */
	public $countryName;

	/** @var int|float */
	public $gmtOffset;
	/** @var int|float */
	public $rawOffset;
	/** @var int|float */
	public $dstOffset;

	/** @var string */
	public $timezoneId;
	/** @var \DateTimeZone */
	public $timezone;

	/** @var Coordinates Generated from lat and lon */
	public $coords;

	public static function fromResponse(\stdClass $response)
	{
		$result = new self();
		foreach ($response as $item => $value) {
			$result->{$item} = $value;
		}

		$result->timezone = new \DateTimeZone($result->timezoneId);

		$result->coords = new Coordinates($result->lat, $result->lng);
		$result->time = new \DateTimeImmutable($result->time, $result->timezone);
		$result->sunset = new \DateTimeImmutable($result->sunset, $result->timezone);
		$result->sunrise = new \DateTimeImmutable($result->sunrise, $result->timezone);

		return $result;
	}

}
