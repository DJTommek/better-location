<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use Utils\Coordinates;
use Utils\General;

final class WG84DegreesService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3}\.[0-9]{1,20})';
	const NAME = 'WG84';

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
		return self::RE_HEMISPHERE . self::RE_OPTIONAL_SPACE . self::RE_COORD . self::RE_HEMISPHERE . self::RE_SPACE_BETWEEN_COORDS . self::RE_HEMISPHERE . self::RE_OPTIONAL_SPACE . self::RE_COORD . self::RE_HEMISPHERE;
	}

	/**
	 * @param $text
	 * @return array<BetterLocation|InvalidLocationException>
	 */
	public static function findInText($text): array {
		$results = [];
		if (preg_match_all('/' . self::getRegex() . '/', $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				try {
					$results[] = self::parseCoords($matches[0][$i]);
				} catch (InvalidLocationException $exception) {
					$results[] = $exception;
				}
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
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $input): BetterLocation {
		if (!preg_match('/^' . self::getRegex() . '$/', $input, $matches)) {
			throw new InvalidLocationException(sprintf('Input is not valid %s coordinates.', self::NAME));
		}
		list($input, $latHemisphere1, $latCoord, $latHemisphere2, $lonHemisphere1, $lonCoord, $lonHemisphere2) = $matches;
		$latCoord = floatval($latCoord);
		$lonCoord = floatval($lonCoord);
		dump($matches);

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
			sprintf(self::NAME),
		);
	}
}
