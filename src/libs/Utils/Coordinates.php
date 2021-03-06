<?php declare(strict_types=1);

namespace App\Utils;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;

class Coordinates
{
	const NORTH = 'N';
	const SOUTH = 'S';
	const EAST = 'E';
	const WEST = 'W';

	const EARTH_RADIUS = 6371000; // in meters

	/** @var float */
	private $lat;
	/** @var float */
	private $lon;

	/**
	 * @param string|int|float $lat Latitude coordinate in WGS-84 format
	 * @param string|int|float $lon Longitude coordinate in WGS-84 format
	 * @throws InvalidLocationException
	 */
	public function __construct($lat, $lon)
	{
		$this->setLat($lat);
		$this->setLon($lon);
	}

	public function getLat(): float
	{
		return $this->lat;
	}

	public function getLon(): float
	{
		return $this->lon;
	}

	public function getLatHemisphere(): string
	{
		return $this->lat >= 0 ? Coordinates::NORTH : Coordinates::SOUTH;
	}
	public function getLonHemisphere(): string
	{
		return $this->lon >= 0 ? Coordinates::EAST : Coordinates::WEST;
	}

	/**
	 * @param string|int|float $lat
	 * @throws InvalidLocationException
	 */
	public function setLat($lat): void
	{
		if (self::isLat($lat) === false) {
			throw new InvalidLocationException('Latitude coordinate must be numeric between or equal from -90 to 90 degrees.');
		}
		$this->lat = Strict::floatval($lat);
	}

	/**
	 * @param string|int|float $lon
	 * @throws InvalidLocationException
	 */
	public function setLon($lon): void
	{
		if (self::isLon($lon) === false) {
			throw new InvalidLocationException('Longitude coordinate must be numeric between or equal from -180 to 180 degrees.');
		}
		$this->lon = Strict::floatval($lon);
	}

	public static function wgs84DegreesToDegreesMinutes(float $degrees): array
	{
		$degreesRound = intval($degrees);
		$minutes = ($degrees - $degreesRound) * 60;
		return [$degreesRound, abs($minutes)];
	}

	public static function wgs84DegreesToDegreesMinutesSeconds(float $degrees): array
	{
		$degreesRound = intval($degrees);
		$minutes = ($degrees - $degreesRound) * 60;
		$minutesRound = intval($minutes);
		$seconds = ($minutes - $minutesRound) * 60;
		return [$degreesRound, abs($minutesRound), abs($seconds)];
	}

	public function __toString(): string
	{
		return sprintf('%F,%F', $this->lat, $this->lon);
	}

	/** Get decimal format from degrees-minutes */
	public static function wgs84DegreesMinutesToDecimal(float $degrees, float $minutes, string $hemisphere): float
	{
		return self::flip($hemisphere) * ($degrees + $minutes / 60);
	}

	/** Get decimal format from degrees-minutes-seconds */
	public static function wgs84DegreesMinutesSecondsToDecimal(float $degrees, float $minutes, float $seconds, string $hemisphere): float
	{
		return self::flip($hemisphere) * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	/**
	 * Convert EXIF format of coordinates to decimal
	 *
	 * @see https://en.wikipedia.org/wiki/Geotagging
	 * @example exifGpsToDecimal(['57/1', '38/1', '5683/100'], S) === -57.64911
	 * @param string[] $exifCoord
	 */
	public static function exifToDecimal(array $exifCoord, string $hemisphere): float
	{
		$degrees = count($exifCoord) > 0 ? self::gpsSubIFDToFloat($exifCoord[0]) : 0;
		$minutes = count($exifCoord) > 1 ? self::gpsSubIFDToFloat($exifCoord[1]) : 0;
		$seconds = count($exifCoord) > 2 ? self::gpsSubIFDToFloat($exifCoord[2]) : 0;

		return self::flip($hemisphere) * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	/**
	 * Convert rational number in the GPS sub-IFD to float
	 *
	 * @see https://en.wikipedia.org/wiki/Geotagging
	 * @example gpsSubIFDToFloat('5683/100') === 56.83
	 */
	public static function gpsSubIFDToFloat(string $coordPart): float
	{
		if (preg_match('/^([0-9]+)\/([0-9]+)$/', $coordPart, $matches)) {
			return (float)$matches[1] / (float)$matches[2];
		} else {
			throw new \InvalidArgumentException(sprintf('Provided part of coordination "%s" is not valid.', $coordPart));
		}
	}

	/**
	 * Get info if decimal coordinates should be negative
	 *
	 * @return int 1|-1
	 */
	public static function flip(string $hemisphere): int
	{
		$hemisphere = mb_strtoupper($hemisphere);
		if (in_array($hemisphere, [self::NORTH, self::EAST, '', '+'], true)) {
			return 1;
		} else if (in_array($hemisphere, [self::WEST, self::SOUTH, '-'], true)) {
			return -1;
		} else {
			throw new \InvalidArgumentException('Invalid hemisphere');
		}
	}

	/**
	 * Calculates the great-circle distance between two points, with the Vincenty formula.
	 *
	 * @param float $latitudeFrom Latitude of start point in [deg decimal]
	 * @param float $longitudeFrom Longitude of start point in [deg decimal]
	 * @param float $latitudeTo Latitude of target point in [deg decimal]
	 * @param float $longitudeTo Longitude of target point in [deg decimal]
	 * @param float $earthRadius Mean earth radius in [m]
	 * @return float Distance between points in [m] (same as earthRadius)
	 * @author https://stackoverflow.com/a/10054282/3334403
	 */
	public static function distance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, float $earthRadius = self::EARTH_RADIUS): float
	{
		// convert from degrees to radians
		$latFrom = deg2rad($latitudeFrom);
		$lonFrom = deg2rad($longitudeFrom);
		$latTo = deg2rad($latitudeTo);
		$lonTo = deg2rad($longitudeTo);

		$lonDelta = $lonTo - $lonFrom;
		$a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

		$angle = atan2(sqrt($a), $b);
		return $angle * $earthRadius;
	}

	/** @param string|int|float $lat */
	public static function isLat($lat): bool
	{
		return (Strict::isFloat($lat) && $lat <= 90 && $lat >= -90);
	}

	/** @param string|int|float $lon */
	public static function isLon($lon): bool
	{
		return (Strict::isFloat($lon) && $lon <= 180 && $lon >= -180);
	}

	public static function getLatLon(string $input, string $separator = ','): ?array
	{
		$coords = explode($separator, $input);
		if (count($coords) === 2 && Coordinates::isLat($coords[0]) && Coordinates::isLon($coords[1])) {
			return [
				Strict::floatval($coords[0]),
				Strict::floatval($coords[1])
			];
		}
		return null;
	}
}
