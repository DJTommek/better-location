<?php declare(strict_types=1);

namespace Utils;

class Coordinates
{
	const NORTH = 'N';
	const SOUTH = 'S';
	const EAST = 'E';
	const WEST = 'W';

	const EARTH_RADIUS = 6371000; // in meters

	// @TODO maybe use universal regex for various of formats?
	// https://regex101.com/r/gD9aU5/1

	// @see https://regexr.com/57rn1
	// 49.9767533N, 14.5672972E
	// 49.9767533N,14.5672972E
	// N49.9767533, E14.5672972
	// N49.9767533,E14.5672972
	// 49.9767533, 14.5672972
	// 49.9767533,14.5672972
	// +49.9767533, -14.5672972
	// +49.9767533,-14.5672972
	// @TODO All coordinates are handled as North and East (N/E, +/+). So S/W and - are ignored
	const RE_WGS84_DEGREES = '/([-+ NSWE])?([0-9]{1,3}\.[0-9]{1,20})([-+ NSWE])?[., ]{1,4}([-+ NSWE])?([0-9]{1,3}\.[0-9]{1,20})([-+ NSWE])?/';

	// @see https://regexr.com/5838t
	// N 49°59.72333', E 14°31.36987'
	// N 10°4.34702', E 78°46.32372'
	// S 41°18.11425', E 174°46.79265'
	// S 51°37.66440', W 69°13.32803'
	const RE_WGS84_DEGREES_MINUTES = '/(([NS]) ?([0-9]{1,2})°([0-9]{1,2}(?:\.[0-9]{1,10})?))\'[ ,]{0,3}(([EW]) ? ?([0-9]{1,3})°([0-9]{1,2}(?:\.[0-9]{1,10})?)\')/';

	// @see https://regexr.com/583bn
	// 51°37'39.864"S, 69°13'19.682"W
	const RE_WGS84_DEGREES_MINUTES_SECONDS = '/(([0-9]{1,2})°([0-9]{1,2})\'([0-9]{1,2}(?:\.[0-9]{1,10})?) ?"([NS]))[ ,]{0,3}(([0-9]{1,3})°([0-9]{1,2})\'([0-9]{1,2}(?:\.[0-9]{1,10})?)" ?([WE]))/';

	/**
	 * Get decimal format from degrees-minutes
	 *
	 * @param float $degrees
	 * @param float $minutes
	 * @param string $hemisphere
	 * @return float
	 */
	public static function wgs84DegreesMinutesToDecimal(float $degrees, float $minutes, string $hemisphere) {
		return self::flip($hemisphere) * ($degrees + $minutes / 60);
	}

	/**
	 * Get decimal format from degrees-minutes
	 *
	 * @param float $degrees
	 * @param float $minutes
	 * @param float $seconds
	 * @param string $hemisphere
	 * @return float
	 */
	public static function wgs84DegreesMinutesSecondsToDecimal(float $degrees, float $minutes, float $seconds, string $hemisphere) {
		return self::flip($hemisphere) * ($degrees + $minutes / 60 + $seconds / 3600);
	}

	/**
	 * Convert EXIF format of coordinates to decimal
	 *
	 * @see https://en.wikipedia.org/wiki/Geotagging
	 * @example exifGpsToDecimal(['57/1', '38/1', '5683/100'], S) === -57.64911
	 * @param string[] $exifCoord
	 * @param string $hemisphere
	 * @return float
	 */
	public static function exifToDecimal(array $exifCoord, string $hemisphere): float {
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
	 * @param $coordPart
	 * @return float
	 */
	public static function gpsSubIFDToFloat(string $coordPart): float {
		$parts = explode('/', $coordPart);
		if (count($parts) <= 0) {
			return 0;
		}
		if (count($parts) == 1) {
			return $parts[0];
		}
		return floatval($parts[0]) / floatval($parts[1]);
	}

	/**
	 * Get info if decimal coordinates should be negative
	 *
	 * @param string $hemisphere
	 * @return int
	 */
	public static function flip(string $hemisphere) {
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
	public static function distance(float $latitudeFrom, float $longitudeFrom, float $latitudeTo, float $longitudeTo, float $earthRadius = self::EARTH_RADIUS) {
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

	/**
	 * Check if point is inside of polygon
	 *
	 * @param float $lat
	 * @param float $lng
	 * @param array $polygon multi-array of coordinates, example: [[50.5,16.5], [51.5,16.5], [51.5,17.5], [50.5,17.5]]
	 * @return bool
	 * @author https://stackoverflow.com/a/18190354/3334403
	 */
	public static function isInPolygon(float $lat, float $lng, array $polygon): bool {
		$c = 0;
		$p1 = $polygon[0];
		$n = count($polygon);

		for ($i = 1; $i <= $n; $i++) {
			$p2 = $polygon[$i % $n];
			if ($lng > min($p1[1], $p2[1])
				&& $lng <= max($p1[1], $p2[1])
				&& $lat <= max($p1[0], $p2[0])
				&& $p1[1] != $p2[1]) {
				$xinters = ($lng - $p1[1]) * ($p2[0] - $p1[0]) / ($p2[1] - $p1[1]) + $p1[0];
				if ($p1[0] == $p2[0] || $lat <= $xinters) {
					$c++;
				}
			}
			$p1 = $p2;
		}
		// if the number of edges we passed through is even, then it's not in the poly.
		return $c % 2 != 0;
	}
}
