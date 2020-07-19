<?php

declare(strict_types=1);

namespace Utils;

class Coordinates
{
	const NORTH = 'N';
	const SOUTH = 'S';
	const EAST = 'E';
	const WEST = 'W';

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
}
