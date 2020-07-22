<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use Utils\Coordinates;
use Utils\General;

abstract class AbstractService extends \BetterLocation\Service\AbstractService
{
	const RE_HEMISPHERE = '([-+NSWE])?';
	/**
	 * Loose version, migh be buggy, eg:
	 * N52.1111 E12.2222 S53.1111 W13.2222
	 */
	const RE_SPACE_BETWEEN_COORDS = '[., ]{1,4}';

	/**
	 * Strict less-buggy version
	 * N52.1111 E12.2222 S53.1111 W13.2222
	 */
//	const RE_SPACE_BETWEEN_COORDS = ', ?';

	const RE_OPTIONAL_SPACE = ' ?';

	abstract public static function getLink(float $lat, float $lon, bool $drive = false);

	abstract public static function isValid(string $input);

	abstract public static function parseCoords(string $input): BetterLocation;

	abstract public static function findInText(string $text): array;

	/**
	 * Handle matches from all WG84* service regexes
	 *
	 * @param string $serviceClass
	 * @param array $matches
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	protected static function processWG84(string $serviceClass, array $matches) {
		switch ($serviceClass) {
			case WG84DegreesService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonHemisphere2) = $matches;
				$latCoord = floatval($latCoordDegrees);
				$lonCoord = floatval($lonCoordDegrees);
				break;
			case WG84DegreesMinutesService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latCoordMinutes, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonCoordMinutes, $lonHemisphere2) = $matches;
				$latCoord = Coordinates::wgs84DegreesMinutesToDecimal(
					floatval($latCoordDegrees),
					floatval($latCoordMinutes),
					Coordinates::NORTH, // @TODO Temporary hack to just fill up function parameters
				);
				$lonCoord = Coordinates::wgs84DegreesMinutesToDecimal(
					floatval($lonCoordDegrees),
					floatval($lonCoordMinutes),
					Coordinates::EAST, // @TODO Temporary hack to just fill up function parameters
				);
				break;
			case WG84DegreesMinutesSecondsService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latCoordMinutes, $latCoordSeconds, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonCoordMinutes, $lonCoordSeconds, $lonHemisphere2) = $matches;
				$latCoord = Coordinates::wgs84DegreesMinutesSecondsToDecimal(
					floatval($latCoordDegrees),
					floatval($latCoordMinutes),
					floatval($latCoordSeconds),
					Coordinates::NORTH, // @TODO Temporary hack to just fill up function parameters
				);
				$lonCoord = Coordinates::wgs84DegreesMinutesSecondsToDecimal(
					floatval($lonCoordDegrees),
					floatval($lonCoordMinutes),
					floatval($lonCoordSeconds),
					Coordinates::EAST, // @TODO Temporary hack to just fill up function parameters
				);
				break;
			default:
				throw new \InvalidArgumentException(sprintf('"%s" is invalid service class name', $serviceClass));
		}

		if ($latHemisphere1 && $latHemisphere2) {
			throw new InvalidLocationException(sprintf('Invalid format of coordinates "<code>%s</code>" - hemisphere is defined twice for first coordinate', $input));
		}
		if ($lonHemisphere1 && $lonHemisphere2) {
			throw new InvalidLocationException(sprintf('Invalid format of coordinates "<code>%s</code>" - hemisphere is defined twice for second coordinate', $input));
		}

		// Get hemisphere for first coordinate
		$latHemisphere = null;
		if ($latHemisphere1 && !$latHemisphere2) {
			// hemisphere is in prefix
			$latHemisphere = mb_strtoupper($latHemisphere1);
		} else {
			// hemisphere is in suffix
			$latHemisphere = mb_strtoupper($latHemisphere2);
		}

		// Convert hemisphere format for first coordinates to ENUM
		$swap = false;
		if (in_array($latHemisphere, ['', '+', 'N'])) {
			$latHemisphere = Coordinates::NORTH;
		} else if (in_array($latHemisphere, ['-', 'S'])) {
			$latHemisphere = Coordinates::SOUTH;
		} else if (in_array($latHemisphere, ['E'])) {
			$swap = true;
			$latHemisphere = Coordinates::EAST;
		} else if (in_array($latHemisphere, ['W'])) {
			$swap = true;
			$latHemisphere = Coordinates::WEST;
		}

		// Get hemisphere for second coordinate
		$lonHemisphere = null;
		if ($lonHemisphere1 && !$lonHemisphere2) {
			// hemisphere is in prefix
			$lonHemisphere = mb_strtoupper($lonHemisphere1);
		} else {
			// hemisphere is in suffix
			$lonHemisphere = mb_strtoupper($lonHemisphere2);
		}

		// Convert hemisphere format for second coordinates to ENUM
		if (in_array($lonHemisphere, ['', '+', 'E'])) {
			$lonHemisphere = Coordinates::EAST;
		} else if (in_array($lonHemisphere, ['-', 'W'])) {
			$lonHemisphere = Coordinates::WEST;
		} else if (in_array($lonHemisphere, ['N'])) {
			$swap = true;
			$lonHemisphere = Coordinates::NORTH;
		} else if (in_array($lonHemisphere, ['S'])) {
			$swap = true;
			$lonHemisphere = Coordinates::SOUTH;
		}


		// Switch lat-lon coordinates if hemisphere is coordinates are set in different order
		// Exx.x Nyy.y -> Nyy.y Exx.x
		if ($swap) {
			General::swap($latHemisphere, $lonHemisphere);
			General::swap($latCoord, $lonCoord);
		}

		// Check if final format of hemispheres and coordinates is valid
		if (in_array($latHemisphere, [Coordinates::EAST, Coordinates::WEST])) {
			throw new InvalidLocationException(sprintf('Both coordinates "<code>%s</code>" are east-west hemisphere', $matches[0]));
		}
		if (in_array($lonHemisphere, [Coordinates::NORTH, Coordinates::SOUTH])) {
			throw new InvalidLocationException(sprintf('Both coordinates "<code>%s</code>" are north-south hemisphere', $matches[0]));
		}

		return new BetterLocation(
			Coordinates::flip($latHemisphere) * $latCoord,
			Coordinates::flip($lonHemisphere) * $lonCoord,
			sprintf($serviceClass::NAME),
		);

	}
}
