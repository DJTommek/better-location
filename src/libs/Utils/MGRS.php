<?php

declare(strict_types=1);

namespace Utils;

/**
 *
 * Copyright (C) 2014 J42 (Julian Aceves)
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 *
 *
 * OPTIMIZED
 *
 * Math & partial boilerplate from: Google's USNGCoder C# Project (http://geochat.googlecode.com/svn/trunk/Source/Geo/USNGCoder.cs)
 *
 * @author https://gist.github.com/j42/2aae5a360624ae2a43d7
 */
class MGRS
{

	# Properties
	const BLOCK_SIZE = 100000;
	const EQUATORIAL_RADIUS = 6378137.0;            // GRS80 ellipsoid (meters)
	const ECC_SQUARED = 0.006694380023;
	const EASTING_OFFSET = 500000.0;        // (meters)
	const NORTHING_OFFSET = 10000000.0;
	const GRIDSQUARE_SET_COL_SIZE = 8;                // column width of grid square set
	const GRIDSQUARE_SET_ROW_SIZE = 20;
	const k0 = 0.9996;
	const UTMGzdLetters = 'NPQRSTUVWX';
	const USNGSqEast = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
	const USNGSqLetOdd = 'ABCDEFGHJKLMNPQRSTUV';
	const USNGSqLetEven = 'FGHJKLMNPQRSTUVABCDE';

	protected $zoneNumber;


	/**
	 * Constructor
	 */
	public function __construct() {
		// UTMtoLL Requirement
		$this->E1 = (1 - sqrt(1 - self::ECC_SQUARED)) / (1 + sqrt(1 - self::ECC_SQUARED));
		$this->ECC_PRIME_SQUARED = self::ECC_SQUARED / (1 - self::ECC_SQUARED);
		$this->DEG_TO_RAD = M_PI / 180;
		$this->RAD_TO_DEG = 180 / M_PI;
	}


	// Number of digits to display for x,y coords
	//  One digit:    10 km precision      eg. "18S UJ 2 1"
	//  Two digits:   1 km precision       eg. "18S UJ 23 06"
	//  Three digits: 100 meters precision eg. "18S UJ 234 064"
	//  Four digits:  10 meters precision  eg. "18S UJ 2348 0647"
	//  Five digits:  1 meter precision    eg. "18S UJ 23480 06470"

	/************* retrieve zone number from latitude, longitude *************
	 *
	 * Zone number ranges from 1 - 60 over the range [-180 to +180]. Each
	 * range is 6 degrees wide. Special cases for points outside normal
	 * [-80 to +84] latitude zone.
	 *************************************************************************/

	public function getZoneNumber($lat, $lon) {

		$lat = floatval($lat);
		$lon = floatval($lon);

		// Sanity Check
		if ($lon > 360 || $lon < -180 || $lat > 90 || $lat < -90) throw new \UnexpectedValueException('Invalid coordinates received');

		// Convert 0-360 to [-180 to 180] range
		$lonTemp = ($lon + 180) - intval(($lon + 180) / 360) * 360 - 180;
		$zoneNumber = intval(($lonTemp + 180) / 6) + 1;

		// Handle special case of west coast of Norway
		if ($lat >= 56 && $lat < 64 && $lonTemp >= 3 && $lonTemp < 12) $zoneNumber = 32;

		// Special zones for Svalbard
		if ($lat >= 72 && $lat < 84) {
			if ($lonTemp >= 0 && $lonTemp < 9) {
				$zoneNumber = 31;
			} else if ($lonTemp >= 9 && $lonTemp < 21) {
				$zoneNumber = 33;
			} else if ($lonTemp >= 21 && $lonTemp < 33) {
				$zoneNumber = 35;
			} else if ($lonTemp >= 33 && $lonTemp < 42) {
				$zoneNumber = 37;
			}
		}
		return $zoneNumber;
	}

	/**
	 * LatLng --> UTM
	 *
	 * Converts lat/long to UTM coords.  Equations from USGS Bulletin 1532
	 *   (or USGS Professional Paper 1395 "Map Projections - A Working Manual",
	 *   by John P. Snyder, U.S. Government Printing Office, 1987.)
	 *
	 *  East Longitudes are positive, West longitudes are negative.
	 *   North latitudes are positive, South latitudes are negative
	 *   lat and lon are in decimal degrees
	 *
	 *  output is in the input array utmcoords
	 *      utmcoords[0] = easting
	 *      utmcoords[1] = northing (NEGATIVE value in southern hemisphere)
	 *      utmcoords[2] = zone
	 */
	public function LLtoUTM($lat, $lon, &$utmcoords, $zone = false) {

		// ! 'utmcords' pass by reference
		$lat = floatval($lat);
		$lon = floatval($lon);

		// Constrain reporting USNG coords to the $latitude range [80S .. 84N]
		if ($lat > 84 || $lat < -80) return null;

		// Sanity check
		if ($lon > 360 || $lon < -180 || $lat > 90 || $lat < -90) throw new \UnexpectedValueException('Invalid coordinates');

		// Make sure the $longitude is between -180.00 .. 179.99..
		// Convert values on 0-360 range to this range.
		$lonTemp = ($lon + 180) - intval(($lon + 180) / 360) * 360 - 180;
		$latRad = $lat * $this->DEG_TO_RAD;
		$lonRad = $lonTemp * $this->DEG_TO_RAD;

		// user-supplied zone number will force coordinates to be computed in a particular zone
		$this->zoneNumber = (!$zone) ? self::getZoneNumber($lat, $lon) : $zone;

		$lonOrigin = ($this->zoneNumber - 1) * 6 - 180 + 3;  // +3 puts origin in middle of zone
		$lonOriginRad = $lonOrigin * $this->DEG_TO_RAD;

		// compute the UTM Zone from the $latitude and $longitude
		$UTMZone = $this->zoneNumber . '' . self::UTMLetterDesignator($lat) . ' ';

		$N = self::EQUATORIAL_RADIUS / sqrt(1 - self::ECC_SQUARED * sin($latRad) * sin($latRad));
		$T = tan($latRad) * tan($latRad);
		$C = $this->ECC_PRIME_SQUARED * cos($latRad) * cos($latRad);
		$A = cos($latRad) * ($lonRad - $lonOriginRad);

		// Note that the term Mo drops out of the "M" equation, because phi
		// ($latitude crossing the central meridian, lambda0, at the origin of the
		//  x,y coordinates), is equal to zero for UTM.
		$M = self::EQUATORIAL_RADIUS * ((1 - self::ECC_SQUARED / 4
					- 3 * (self::ECC_SQUARED * self::ECC_SQUARED) / 64
					- 5 * (self::ECC_SQUARED * self::ECC_SQUARED * self::ECC_SQUARED) / 256) * $latRad
				- (3 * self::ECC_SQUARED / 8 + 3 * self::ECC_SQUARED * self::ECC_SQUARED / 32
					+ 45 * self::ECC_SQUARED * self::ECC_SQUARED * self::ECC_SQUARED / 1024)
				* sin(2 * $latRad) + (15 * self::ECC_SQUARED * self::ECC_SQUARED / 256
					+ 45 * self::ECC_SQUARED * self::ECC_SQUARED * self::ECC_SQUARED / 1024) * sin(4 * $latRad)
				- (35 * self::ECC_SQUARED * self::ECC_SQUARED * self::ECC_SQUARED / 3072) * sin(6 * $latRad));

		$UTMEasting = (self::k0 * $N * ($A + (1 - $T + $C) * ($A * $A * $A) / 6
				+ (5 - 18 * $T + $T * $T + 72 * $C - 58 * $this->ECC_PRIME_SQUARED)
				* ($A * $A * $A * $A * $A) / 120)
			+ self::EASTING_OFFSET);

		$UTMNorthing = (self::k0 * ($M + $N * tan($latRad) * (($A * $A) / 2 + (5 - $T + 9
						* $C + 4 * $C * $C) * ($A * $A * $A * $A) / 24
					+ (61 - 58 * $T + $T * $T + 600 * $C - 330 * $this->ECC_PRIME_SQUARED)
					* ($A * $A * $A * $A * $A * $A) / 720)));

		$utmcoords[0] = $UTMEasting;
		$utmcoords[1] = $UTMNorthing;
		$utmcoords[2] = $this->zoneNumber;
	}

	/**
	 * LatLng --> USNG
	 *
	 * Converts lat/lng to USNG coordinates.  Calls LLtoUTM first, then
	 * converts UTM coordinates to a USNG string.
	 *
	 *   Returns string of the format: DDL LL DDDD DDDD (4-digit precision), eg:
	 *     "18S UJ 2286 0705" locates Washington Monument in Washington, D.C.
	 *     to a 10-meter precision.
	 */
	public function LLtoUSNG($lat, $lon, $precision) {

		$lat = floatval($lat);
		$lon = floatval($lon);

		// convert lat/lon to UTM coordinates
		$coords = [];
		self::LLtoUTM($lat, $lon, $coords);
		$UTMEasting = $coords[0];
		$UTMNorthing = $coords[1];

		// ...then convert UTM to USNG
		// southern hemispher case
		if ($lat < 0) $UTMNorthing += self::NORTHING_OFFSET;

		$USNGLetters = self::findGridLetters($this->zoneNumber, $UTMNorthing, $UTMEasting);
		$USNGNorthing = round($UTMNorthing) % self::BLOCK_SIZE;
		$USNGEasting = round($UTMEasting) % self::BLOCK_SIZE;

		// added... truncate digits to achieve specified precision
		$USNGNorthing = floor($USNGNorthing / pow(10, (5 - $precision)));
		$USNGEasting = floor($USNGEasting / pow(10, (5 - $precision)));
		$USNG = self::getZoneNumber($lat, $lon) . self::UTMLetterDesignator($lat) . ' ' . $USNGLetters . ' ';

		// REVISIT: Modify to incorporate dynamic precision ?
		for ($i = strlen(strval($USNGEasting)); $i < $precision; ++$i) $USNG .= '0';
		$USNG .= $USNGEasting . ' ';
		for ($i = strlen(strval($USNGNorthing)); $i < $precision; ++$i) $USNG .= '0';
		$USNG .= $USNGNorthing;

		return $USNG;
	}

	/**
	 * UTM --> LatLng
	 *
	 * Equations from USGS Bulletin 1532 (or USGS Professional Paper 1395)
	 *   East Longitudes are positive, West longitudes are negative.
	 *   North latitudes are positive, South latitudes are negative.
	 *
	 *   Expected Input args:
	 *     UTMNorthing   : northing-m (numeric), eg. 432001.8
	 *       southern hemisphere NEGATIVE from equator ('real' value - 10,000,000)
	 *     UTMEasting    : easting-m  (numeric), eg. 4000000.0
	 *     UTMZoneNumber : 6-deg longitudinal zone (numeric), eg. 18
	 */
	public function UTMLtoLL($UTMNorthing, $UTMEasting, $UTMZoneNumber, &$ret) {
		// remove 500,000 meter offset for longitude
		$xUTM = floatval($UTMEasting) - self::EASTING_OFFSET;
		$yUTM = floatval($UTMNorthing);
		$this->zoneNumber = intval($UTMZoneNumber);

		// origin longitude for the zone (+3 puts origin in zone center)
		$lonOrigin = ($this->zoneNumber - 1) * 6 - 180 + 3;

		// M is the "true distance along the central meridian from the Equator to phi
		// (latitude)
		$M = $yUTM / self::k0;
		$mu = $M / (self::EQUATORIAL_RADIUS * (1 - self::ECC_SQUARED / 4 - 3 * self::ECC_SQUARED *
					self::ECC_SQUARED / 64 - 5 * self::ECC_SQUARED * self::ECC_SQUARED * self::ECC_SQUARED / 256));

		// phi1 is the "footprint latitude" or the latitude at the central meridian which
		// has the same y coordinate as that of the point (phi (lat), lambda (lon) ).
		$phi1Rad = $mu + (3 * $this->E1 / 2 - 27 * $this->E1 * $this->E1 * $this->E1 / 32) * sin(2 * $mu)
			+ (21 * $this->E1 * $this->E1 / 16 - 55 * $this->E1 * $this->E1 * $this->E1 * $this->E1 / 32) * sin(4 * $mu)
			+ (151 * $this->E1 * $this->E1 * $this->E1 / 96) * sin(6 * $mu);
		$phi1 = $phi1Rad * $this->RAD_TO_DEG;

		// Terms used in the conversion equations
		$N1 = self::EQUATORIAL_RADIUS / sqrt(1 - self::ECC_SQUARED * sin($phi1Rad) * sin($phi1Rad));
		$T1 = tan($phi1Rad) * tan($phi1Rad);
		$C1 = $this->ECC_PRIME_SQUARED * cos($phi1Rad) * cos($phi1Rad);
		$R1 = self::EQUATORIAL_RADIUS * (1 - self::ECC_SQUARED) / pow(1 - self::ECC_SQUARED * sin($phi1Rad) * sin($phi1Rad), 1.5);
		$D = $xUTM / ($N1 * self::k0);

		// Calculate latitude, in decimal degrees
		$lat = $phi1Rad - ($N1 * tan($phi1Rad) / $R1) * ($D * $D / 2 - (5 + 3 * $T1 + 10
					* $C1 - 4 * $C1 * $C1 - 9 * $this->ECC_PRIME_SQUARED) * $D * $D * $D * $D / 24 + (61 + 90 *
					$T1 + 298 * $C1 + 45 * $T1 * $T1 - 252 * $this->ECC_PRIME_SQUARED - 3 * $C1 * $C1) * $D * $D *
				$D * $D * $D * $D / 720);
		$lat *= $this->RAD_TO_DEG;

		// Calculate longitude, in decimal degrees
		$lon = ($D - (1 + 2 * $T1 + $C1) * $D * $D * $D / 6 + (5 - 2 * $C1 + 28 * $T1 - 3 *
					$C1 * $C1 + 8 * $this->ECC_PRIME_SQUARED + 24 * $T1 * $T1) * $D * $D * $D * $D * $D / 120) /
			cos($phi1Rad);

		$lon = $lonOrigin + $lon * $this->RAD_TO_DEG;
		$ret['lat'] = $lat;
		$ret['lon'] = $lon;
		return $ret;
	}

	/**
	 * USNG --> UTM
	 * Expected Input args:
	 *     zone: Zone (integer), eg. 18
	 *     let: Zone letter, eg S
	 *     sq1:  1st USNG square letter, eg U
	 *     sq2:  2nd USNG square Letter, eg J
	 *     east:  Easting digit string, eg 4000
	 *     north:  Northing digit string eg 4000
	 *     ret:  saves zone,let,Easting and Northing as properties ret
	 */
	public function USNGtoUTM($zone, $let, $sq1, $sq2, $east, $north, &$ret) {

		//Starts (southern edge) of N-S zones in millons of meters
		$zoneBase = [1.1, 2.0, 2.9, 3.8, 4.7, 5.6, 6.5, 7.3, 8.2, 9.1, 0, 0.8, 1.7, 2.6, 3.5, 4.4, 5.3, 6.2, 7.0, 7.9];
		$segBase = [0, 2, 2, 2, 4, 4, 6, 6, 8, 8, 0, 0, 0, 2, 2, 4, 4, 6, 6, 6];  //Starts of 2 million meter segments, indexed by zone

		// convert easting to UTM
		$eSqrs = strpos(self::USNGSqEast, $sq1);
		$appxEast = 1 + $eSqrs % 8;

		// convert northing to UTM
		$letNorth = strpos("CDEFGHJKLMNPQRSTUVWX", $let);
		$nSqrs = ($zone % 2) ? strpos("ABCDEFGHJKLMNPQRSTUV", $sq2) : strpos("FGHJKLMNPQRSTUVABCDE", $sq2);

		$zoneStart = $zoneBase[$letNorth];
		$appxNorth = $segBase[$letNorth] + $nSqrs / 10;

		if ($appxNorth < $zoneStart) $appxNorth += 2;

		$ret['N'] = $appxNorth * 1000000 + intval($north) * pow(10, 5 - strlen($north));
		$ret['E'] = $appxEast * 100000 + intval($east) * pow(10, 5 - strlen($east));
		$ret['zone'] = $zone;
		$ret['letter'] = $let;

		return $ret;
	}


	/**
	 * USNG --> LatLng
	 */
	public function USNGtoLL($usngStr, &$latlon) {
		// latlon is a 2-element array declared by calling routine

		$usngp = [];
		$coords = [];

		self::parseUSNG($usngStr, $usngp);

		// convert USNG coords to UTM; this routine counts digits and sets precision
		self::USNGtoUTM($usngp['zone'], $usngp['let'], $usngp['sq1'], $usngp['sq2'], $usngp['east'], $usngp['north'], $coords);

		// southern hemisphere case
		if ($usngp['let'] < 'N') $coords['N'] -= self::NORTHING_OFFSET;
		self::UTMLtoLL($coords['N'], $coords['E'], $usngp['zone'], $coords);
		$latlon[0] = $coords['lat'];
		$latlon[1] = $coords['lon'];
	}

	/**
	 * LatLng --> MGRS
	 */
	public function LLtoMGRS($lat, $lon, $precision) {
		return preg_replace('/\s/', '', self::LLtoUSNG($lat, $lon, $precision));
	}


	// Valid USNG String?
	public static function isUSNG($usngStr) {
		$j = 0;
		$k = 0;

		// Construct String
		$usngStr = str_ireplace(['%20', ' '], ['', ''], strtoupper($usngStr));
		$precision = '^[0-9]{2}[CDEFGHJKLMNPQRSTUVWX]$';
		$generic = '^[0-9]{2}[CDEFGHJKLMNPQRSTUVWX][ABCDEFGHJKLMNPQRSTUVWXYZ][ABCDEFGHJKLMNPQRSTUV]([0-9][0-9]){0,5}';

		// Invalid States || usngStr
		if (preg_match($precision, $usngStr) || preg_match($generic, $usngStr) || strlen($usngStr) > 15) {
			return false;
		} else return $usngStr;
	}


	/**
	 * This routine determines the correct UTM letter designator for the given
	 * latitude returns 'Z' if latitude is outside the UTM limits of 84N to 80S
	 *
	 * Returns letter designator for a given latitude.
	 * Letters range from C (-80 lat) to X (+84 lat), with each zone spanning
	 * 8 degrees of latitude.
	 */
	private function UTMLetterDesignator($lat) {
		$lat = floatval($lat);

		if ((84 >= $lat) && ($lat >= 72))
			$letterDesignator = 'X';
		else if ((72 > $lat) && ($lat >= 64))
			$letterDesignator = 'W';
		else if ((64 > $lat) && ($lat >= 56))
			$letterDesignator = 'V';
		else if ((56 > $lat) && ($lat >= 48))
			$letterDesignator = 'U';
		else if ((48 > $lat) && ($lat >= 40))
			$letterDesignator = 'T';
		else if ((40 > $lat) && ($lat >= 32))
			$letterDesignator = 'S';
		else if ((32 > $lat) && ($lat >= 24))
			$letterDesignator = 'R';
		else if ((24 > $lat) && ($lat >= 16))
			$letterDesignator = 'Q';
		else if ((16 > $lat) && ($lat >= 8))
			$letterDesignator = 'P';
		else if ((8 > $lat) && ($lat >= 0))
			$letterDesignator = 'N';
		else if ((0 > $lat) && ($lat >= -8))
			$letterDesignator = 'M';
		else if ((-8 > $lat) && ($lat >= -16))
			$letterDesignator = 'L';
		else if ((-16 > $lat) && ($lat >= -24))
			$letterDesignator = 'K';
		else if ((-24 > $lat) && ($lat >= -32))
			$letterDesignator = 'J';
		else if ((-32 > $lat) && ($lat >= -40))
			$letterDesignator = 'H';
		else if ((-40 > $lat) && ($lat >= -48))
			$letterDesignator = 'G';
		else if ((-48 > $lat) && ($lat >= -56))
			$letterDesignator = 'F';
		else if ((-56 > $lat) && ($lat >= -64))
			$letterDesignator = 'E';
		else if ((-64 > $lat) && ($lat >= -72))
			$letterDesignator = 'D';
		else if ((-72 > $lat) && ($lat >= -80))
			$letterDesignator = 'C';
		else
			$letterDesignator = 'Z'; // This is here as an error flag to show
		// that the latitude is outside the UTM limits
		return $letterDesignator;
	}

	private function findSet($zoneNum) {
		switch (intval($zoneNum) % 6) {

			case 0:
				return 6;
				break;

			case 1:
				return 1;
				break;

			case 2:
				return 2;
				break;

			case 3:
				return 3;
				break;

			case 4:
				return 4;
				break;

			case 5:
				return 5;
				break;

			default:
				return -1;
				break;

		}
	}

	/**
	 * Retrieve the square identification for a given coordinate pair & zone
	 */
	private function findGridLetters($zoneNum, $northing, $easting) {

		$zoneNum = intval($zoneNum);
		$northing = floatval($northing);
		$easting = floatval($easting);
		$row = 1;

		// $northing coordinate to single-meter precision
		$north_1m = round($northing);

		// Get the row position for the square identifier that contains the point
		while ($north_1m >= self::BLOCK_SIZE) {
			$north_1m -= self::BLOCK_SIZE;
			++$row;
		}

		// cycle repeats (wraps) after 20 rows
		$row = $row % self::GRIDSQUARE_SET_ROW_SIZE;
		$col = 0;

		// $easting coordinate to single-meter precision
		$east_1m = round($easting);

		// Get the column position for the square identifier that contains the point
		while ($east_1m >= self::BLOCK_SIZE) {
			$east_1m -= self::BLOCK_SIZE;
			++$col;
		}

		// cycle repeats (wraps) after 8 columns
		$col = $col % self::GRIDSQUARE_SET_COL_SIZE;

		return self::lettersHelper(self::findSet($zoneNum), $row, $col);
	}


	/**
	 * Retrieve the Square Identification (two-character letter code), for the
	 *   given row, column and set identifier (set refers to the zone set:
	 *   zones 1-6 have a unique set of square identifiers; these identifiers are
	 *   repeated for zones 7-12, etc.)
	 */
	private function lettersHelper($set, $row, $col) {

		// handle case of last row
		if ($row == 0) {
			$row = self::GRIDSQUARE_SET_ROW_SIZE - 1;
		} else --$row;

		// handle case of last column
		if ($col == 0) {
			$col = self::GRIDSQUARE_SET_COL_SIZE - 1;
		} else --$col;

		$even = ($set % 2 === 0);
		switch ($set) {
			case 1:
			case 4:
				$l1 = 'ABCDEFGH';
				$l2 = ($even) ? 'FGHJKLMNPQRSTUVABCDE' : 'ABCDEFGHJKLMNPQRSTUV';
				break;

			case 2:
			case 5:
				$l1 = 'JKLMNPQR';
				$l2 = ($even) ? 'FGHJKLMNPQRSTUVABCDE' : 'ABCDEFGHJKLMNPQRSTUV';
				break;

			case 3:
			case 6:
				$l1 = 'STUVWXYZ';
				$l2 = ($even) ? 'FGHJKLMNPQRSTUVABCDE' : 'ABCDEFGHJKLMNPQRSTUV';
				break;
		}

		return $l1{$col} . $l2{$row};
	}

	/**
	 * Parse USNG string into it's parts
	 *
	 * It's safe to not use mb_* because it always contains only ASCII characters
	 *
	 * @param string $usngStr
	 * @param string[] $parts
	 */
	private function parseUSNG($usngStr, &$parts) {

		// Construct String
		$usngStr = str_ireplace(['%20', ' '], ['', ''], strtoupper($usngStr));

		// Minimum Range Requirement
		if (strlen($usngStr) < 7) {
			throw new \UnexpectedValueException("This application requires minimum USNG precision of 10,000 meters");
		}

		$parts['zone'] = substr($usngStr, 0, 2);
		$parts['let'] = substr($usngStr, 2, 1);

		$parts['sq1'] = substr($usngStr, 3, 1);
		$parts['sq2'] = substr($usngStr, 4, 1);

		$eastingNorthingString = substr($usngStr, 5);
		$precision = strlen($eastingNorthingString);
		if ($precision % 2 === 1) {
			throw new \UnexpectedValueException(sprintf('Last part (Easting and Northing) must have even length but has "%d" (%s)', $precision, $eastingNorthingString));
		}
		list($easting, $northing) = str_split($eastingNorthingString, $precision / 2);

		$parts['east'] = $easting;
		$parts['north'] = $northing;
	}
}