<?php declare(strict_types=1);

namespace App\Utils;

use App\BetterLocation\Service\Exceptions\InvalidLocationException;

class Coordinates implements CoordinatesInterface
{
	const RE_BASIC_LAT = '-?[0-9]{1,2}\.[0-9]{1,99}';
	const RE_BASIC_LON = '-?[0-9]{1,3}\.[0-9]{1,99}';
	const RE_BASIC = '-?[0-9]{1,2}\.[0-9]{1,99},-?[0-9]{1,3}\.[0-9]{1,99}';

	const NORTH = 'N';
	const SOUTH = 'S';
	const EAST = 'E';
	const WEST = 'W';

	const EARTH_RADIUS = 6371000; // in meters

	private float $lat;
	private float $lon;
	private ?float $elevation; // in meters

	/**
	 * @param string|int|float $lat Latitude coordinate in WGS-84 format
	 * @param string|int|float $lon Longitude coordinate in WGS-84 format
	 * @param int|float|null $elevation Elevation
	 * @throws InvalidLocationException
	 */
	public function __construct($lat, $lon, $elevation = null)
	{
		$this->setLat($lat);
		$this->setLon($lon);
		$this->setElevation($elevation);
	}

	public function getLat(): float
	{
		return $this->lat;
	}

	public function getLon(): float
	{
		return $this->lon;
	}

	/** @return float|null */
	public function getElevation(): ?float
	{
		return $this->elevation;
	}

	public function getLatHemisphere(): string
	{
		return $this->lat >= 0 ? self::NORTH : self::SOUTH;
	}

	public function getLonHemisphere(): string
	{
		return $this->lon >= 0 ? self::EAST : self::WEST;
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

	/**
	 * @param string|int|float|null $elevation
	 * @throws InvalidLocationException
	 */
	public function setElevation($elevation): void
	{
		if (is_null($elevation)) {
			$this->elevation = null;
		} else if (Strict::isFloat($elevation)) {
			$this->elevation = Strict::floatval($elevation);
		} else {
			throw new InvalidLocationException('Elevation must be numeric or null.');
		}
	}

	/** Create new instance but return null if lat and/or lon are invalid */
	public static function safe($lat, $lon, $elevation = null): ?Coordinates
	{
		try {
			return new Coordinates($lat, $lon, $elevation);
		} catch (InvalidLocationException) {
			return null;
		}
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

	public function hash(): string
	{
		return md5($this->__toString());
	}

	public function key(): string
	{
		return sprintf('%F,%F', $this->lat, $this->lon);
	}

	public function __toString(): string
	{
		return $this->key();
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
	 * @return float Distance between points in meters (same unit as self::EARTH_RADIUS)
	 * @author https://stackoverflow.com/a/10054282/3334403
	 */
	public function distance(self $coords): float
	{
		// convert from degrees to radians
		$latFrom = deg2rad($this->lat);
		$lonFrom = deg2rad($this->lon);
		$latTo = deg2rad($coords->lat);
		$lonTo = deg2rad($coords->lon);

		$lonDelta = $lonTo - $lonFrom;
		$a = pow(cos($latTo) * sin($lonDelta), 2) + pow(cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta), 2);
		$b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

		$angle = atan2(sqrt($a), $b);
		return $angle * self::EARTH_RADIUS;
	}

	public static function distanceLatLon(float $lat1, float $lon1, float $lat2, float $lon2): float
	{
		$location1 = new Coordinates($lat1, $lon1);
		$location2 = new Coordinates($lat2, $lon2);

		return $location1->distance($location2);
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

	/**
	 * Safely create Coordinates object from format 'latitude,longitude' or return null
	 */
	public static function fromString(string $input, string $separator = ','): ?self
	{
		$coords = explode($separator, $input);
		if (count($coords) === 2) {
			return self::safe($coords[0], $coords[1]);
		}
		return null;
	}

	/**
	 * Check if point is inside of polygon
	 *
	 * @param array $polygon multi-array of coordinates, example: [[50.5,16.5], [51.5,16.5], [51.5,17.5], [50.5,17.5]]
	 * @author https://stackoverflow.com/a/18190354/3334403
	 */
	public function isInPolygon(array $polygon): bool
	{
		$c = 0;
		$p1 = $polygon[0];
		$n = count($polygon);

		for ($i = 1; $i <= $n; $i++) {
			$p2 = $polygon[$i % $n];
			if ($this->lon > min($p1[1], $p2[1])
				&& $this->lon <= max($p1[1], $p2[1])
				&& $this->lat <= max($p1[0], $p2[0])
				&& $p1[1] != $p2[1]) {
				$xinters = ($this->lon - $p1[1]) * ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]) + $p1[0];
				if ($p1[0] == $p2[0] || $this->lat <= $xinters) {
					$c++;
				}
			}
			$p1 = $p2;
		}
		// if the number of edges we passed through is even, then it's not in the poly.
		return $c % 2 != 0;
	}
}
