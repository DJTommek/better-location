<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use Utils\Coordinates;

final class WG84DegreesMinutesSecondsService extends AbstractService
{
	const RE_COORD = '([0-9]{1,2}°[0-9]{1,2}\'[0-9]{1,2}(?:\.[0-9]{1,10})?)"';
	const NAME = 'WG84 DMS';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotSupportedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		throw new NotSupportedException('Link for raw coordinates is not supported.');
	}

	public static function getRegex(): string {
		return self::RE_HEMISPHERE . self::RE_OPTIONAL_SPACE . self::RE_COORD . self::RE_OPTIONAL_SPACE . self::RE_HEMISPHERE . self::RE_SPACE_BETWEEN_COORDS . self::RE_HEMISPHERE . self::RE_OPTIONAL_SPACE . self::RE_COORD . self::RE_OPTIONAL_SPACE . self::RE_HEMISPHERE;
	}

	/**
	 * @param $text
	 * @return array<BetterLocation|\Exception>
	 */
	public static function findInText($text): array {
		$results = [];
		if (preg_match_all('/' . self::getRegex() . '/', $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				if ($matches[1][$i] && $matches[3][$i]) {
					$results[] = new InvalidLocationException(sprintf('Invalid format of coordinates "<code>%s</code>" - hemisphere is defined twice for first coordinate', $matches[0][$i]));
					continue;
				}
				if ($matches[4][$i] && $matches[6][$i]) {
					$results[] = new InvalidLocationException(sprintf('Invalid format of coordinates "<code>%s</code>" - hemisphere is defined twice for second coordinate', $matches[0][$i]));
					continue;
				}

				// Get hemisphere for first coordinate
				$latHemisphere = null;
				if ($matches[1][$i] && !$matches[3][$i]) {
					// hemisphere is in prefix
					$latHemisphere = mb_strtoupper($matches[1][$i]);
				} else {
					// hemisphere is in suffix
					$latHemisphere = mb_strtoupper($matches[3][$i]);
				}

				// Convert hemisphere format for first coordinates to ENUM
				$switch = false;
				if (in_array($latHemisphere, ['', '+', 'N'])) {
					$latHemisphere = Coordinates::NORTH;
				} else if (in_array($latHemisphere, ['-', 'S'])) {
					$latHemisphere = Coordinates::SOUTH;
				} else if (in_array($latHemisphere, ['E'])) {
					$switch = true;
					$latHemisphere = Coordinates::EAST;
				} else if (in_array($latHemisphere, ['W'])) {
					$switch = true;
					$latHemisphere = Coordinates::WEST;
				}

				// Get hemisphere for second coordinate
				$lonHemisphere = null;
				if ($matches[4][$i] && !$matches[6][$i]) {
					// hemisphere is in prefix
					$lonHemisphere = mb_strtoupper($matches[4][$i]);
				} else {
					// hemisphere is in suffix
					$lonHemisphere = mb_strtoupper($matches[6][$i]);
				}

				// Convert hemisphere format for second coordinates to ENUM
				if (in_array($lonHemisphere, ['', '+', 'E'])) {
					$lonHemisphere = Coordinates::EAST;
				} else if (in_array($lonHemisphere, ['-', 'W'])) {
					$lonHemisphere = Coordinates::WEST;
				} else if (in_array($lonHemisphere, ['N'])) {
					$switch = true;
					$lonHemisphere = Coordinates::NORTH;
				} else if (in_array($lonHemisphere, ['S'])) {
					$switch = true;
					$lonHemisphere = Coordinates::SOUTH;
				}

				// Switch lat-lon coordinates if hemisphere is coordinates are set in different order
				// Exx°xx.x Nyy°yy.y -> Nyy°yy.y Exx°xx.x
				if ($switch) {
					list($latDegrees, $latTemp) = explode('°', $matches[5][$i]);
					list($latMinutes, $latSeconds) = explode('\'', $latTemp);

					list($lonDegrees, $lonTemp) = explode('°', $matches[2][$i]);
					list($lonMinutes, $lonSeconds) = explode('\'', $lonTemp);

					$temp = $latHemisphere;
					$latHemisphere = $lonHemisphere;
					$lonHemisphere = $temp;
				} else {
					list($latDegrees, $latTemp) = explode('°', $matches[2][$i]);
					list($latMinutes, $latSeconds) = explode('\'', $latTemp);

					list($lonDegrees, $lonTemp) = explode('°', $matches[5][$i]);
					list($lonMinutes, $lonSeconds) = explode('\'', $lonTemp);
				}

				// Check if final format of hemispheres and coordinates is valid
				if (in_array($latHemisphere, [Coordinates::EAST, Coordinates::WEST])) {
					$results[] = new InvalidLocationException(sprintf('Both coordinates "<code>%s</code>" are east-west hemisphere', $matches[0][$i]));
					continue;
				}
				if (in_array($lonHemisphere, [Coordinates::NORTH, Coordinates::SOUTH])) {
					$results[] = new InvalidLocationException(sprintf('Both coordinates "<code>%s</code>" are north-south hemisphere', $matches[0][$i]));
					continue;
				}

				$results[] = new BetterLocation(
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($latDegrees), floatval($latMinutes), floatval($latSeconds), $latHemisphere),
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($lonDegrees), floatval($lonMinutes), floatval($lonSeconds), $lonHemisphere),
					sprintf(self::NAME),
				);
			}
		}
		return $results;
	}

	public static function isValid(string $input): bool {
		return !!preg_match('/^' . self::getRegex() . '$/', $input);
	}

	/**
	 * @param string $input
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoords(string $input) {
		throw new NotImplementedException('Parsing coordinates is not implemented');
	}
}
