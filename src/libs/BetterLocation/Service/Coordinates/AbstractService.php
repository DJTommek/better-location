<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\Utils\Coordinates;
use App\Utils\Strict;
use App\Utils\Utils;

abstract class AbstractService extends \App\BetterLocation\Service\AbstractService
{
	const TAGS = [
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	/**
	 * Handle matches from all WGS84* service regexes
	 * @throws InvalidLocationException
	 */
	protected function processWGS84(): BetterLocation
	{
		switch (static::class) {
			case WGS84DegreesService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonHemisphere2) = array_pad($this->data->matches, 7, '');
				$latCoord = Strict::floatval($latCoordDegrees);
				$lonCoord = Strict::floatval($lonCoordDegrees);
				break;
			case WGS84DegreesMinutesService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latCoordMinutes, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonCoordMinutes, $lonHemisphere2) = array_pad($this->data->matches, 9, '');
				$latCoord = Coordinates::wgs84DegreesMinutesToDecimal(
					Strict::floatval($latCoordDegrees),
					Strict::floatval($latCoordMinutes),
					Coordinates::NORTH // Temporary fill default value
				);
				$lonCoord = Coordinates::wgs84DegreesMinutesToDecimal(
					Strict::floatval($lonCoordDegrees),
					Strict::floatval($lonCoordMinutes),
					Coordinates::EAST // Temporary fill default value
				);
				break;
			case WGS84DegreesMinutesSecondsService::class:
				list($input, $latHemisphere1, $latCoordDegrees, $latCoordMinutes, $latCoordSeconds, $latHemisphere2, $lonHemisphere1, $lonCoordDegrees, $lonCoordMinutes, $lonCoordSeconds, $lonHemisphere2) = array_pad($this->data->matches, 11, '');
				$latCoord = Coordinates::wgs84DegreesMinutesSecondsToDecimal(
					Strict::floatval($latCoordDegrees),
					Strict::floatval($latCoordMinutes),
					Strict::floatval($latCoordSeconds),
					Coordinates::NORTH // Temporary fill default value
				);
				$lonCoord = Coordinates::wgs84DegreesMinutesSecondsToDecimal(
					Strict::floatval($lonCoordDegrees),
					Strict::floatval($lonCoordMinutes),
					Strict::floatval($lonCoordSeconds),
					Coordinates::EAST // Temporary fill default value
				);
				break;
			default:
				throw new \InvalidArgumentException(sprintf('"%s" is invalid service class name', static::class));
		}

		// '50.087451-,13.420671' is nonsense, fix to '50.087451,13.420671'
		if (trim($latHemisphere2) === '-') {
			$latHemisphere2 = '';
		}
		// '50.087451,13.420671-' is nonsense, fix to '50.087451,13.420671'
		if (trim($lonHemisphere2) === '-') {
			$lonHemisphere2 = '';
		}

		// '- 50.087451,13.420671' do not allow spaces between hyphen and coordinate
		// @TODO do this also for longitude
		if (trim($latHemisphere1) === '-' && preg_match('/^-\s+/', trim($input))) {
			$latHemisphere1 = '';
		}

		// regex wrongly detected two hemisphere for first coordinate
		if ($latHemisphere1 && $latHemisphere2 && !$lonHemisphere1 && !$lonHemisphere2) {
			$lonHemisphere1 = $latHemisphere2;
			$latHemisphere2 = '';
		}

		/**
		 * First coordinate has detected hemispere symbols before and after text
		 *
		 * @see \Tests\BetterLocation\Service\Coordinates\WGS84DegreesServiceTest::testDynamicHemispherePositionFirst()
		 */
		if ($latHemisphere1 && $latHemisphere2) {
			if ($lonHemisphere1 === '' && $lonHemisphere2 !== '') {
				// Hemisphere for second coordinate is defined only after coordinate number so for let's use after for first coordinate too
				$latHemisphere1 = '';
			} else if ($lonHemisphere1 !== '' && $lonHemisphere2 === '') {
				// Hemisphere for second coordinate is defined only before coordinate number so for let's use after for first coordinate too
				$latHemisphere2 = '';
			} else {
				throw new InvalidLocationException(sprintf('Invalid format of coordinates "%s" - hemisphere is defined twice for first coordinate', $input));
			}
		}

		/**
		 * Second coordinate has detected hemispere symbols before and after text
		 *
		 * @see \Tests\BetterLocation\Service\Coordinates\WGS84DegreesServiceTest::testDynamicHemispherePositionSecond()
		 */
		if ($lonHemisphere1 && $lonHemisphere2) {
			if ($latHemisphere1 === '' && $latHemisphere2 !== '') {
				// Hemisphere for first coordinate is defined only after coordinate number so for let's use after for second coordinate too
				$lonHemisphere1 = '';
			} else if ($latHemisphere1 !== '' && $latHemisphere2 === '') {
				// Hemisphere for first coordinate is defined only before coordinate number so for let's use after for second coordinate too
				$lonHemisphere2 = '';
			} else {
				throw new InvalidLocationException(sprintf('Invalid format of coordinates "%s" - hemisphere is defined twice for second coordinate', $input));
			}
		}

		// Get hemisphere for first coordinate
		if ($latHemisphere1 && !$latHemisphere2) {
			// hemisphere is in prefix
			$latHemisphere = mb_strtoupper($latHemisphere1);
		} else {
			// hemisphere is in suffix
			$latHemisphere = mb_strtoupper($latHemisphere2);
		}

		// Convert hemisphere format for first coordinates to ENUM
		$swap = false;
		if (in_array($latHemisphere, ['', '+', 'N'], true)) {
			$latHemisphere = Coordinates::NORTH;
		} else if (in_array($latHemisphere, ['-', 'S'], true)) {
			$latHemisphere = Coordinates::SOUTH;
		} else if ($latHemisphere === 'E') {
			$swap = true;
			$latHemisphere = Coordinates::EAST;
		} else if ($latHemisphere === 'W') {
			$swap = true;
			$latHemisphere = Coordinates::WEST;
		}

		// Get hemisphere for second coordinate
		if ($lonHemisphere1 && !$lonHemisphere2) {
			// hemisphere is in prefix
			$lonHemisphere = mb_strtoupper($lonHemisphere1);
		} else {
			// hemisphere is in suffix
			$lonHemisphere = mb_strtoupper($lonHemisphere2);
		}

		// Convert hemisphere format for second coordinates to ENUM
		if (in_array($lonHemisphere, ['', '+', 'E'], true)) {
			$lonHemisphere = Coordinates::EAST;
		} else if (in_array($lonHemisphere, ['-', 'W'], true)) {
			$lonHemisphere = Coordinates::WEST;
		} else if ($lonHemisphere === 'N') {
			$swap = true;
			$lonHemisphere = Coordinates::NORTH;
		} else if ($lonHemisphere === 'S') {
			$swap = true;
			$lonHemisphere = Coordinates::SOUTH;
		}

		// Switch lat-lon coordinates if hemisphere is coordinates are set in different order
		// Exx.x Nyy.y -> Nyy.y Exx.x
		if ($swap) {
			Utils::swap($latHemisphere, $lonHemisphere);
			Utils::swap($latCoord, $lonCoord);
		}

		// Check if final format of hemispheres and coordinates is valid
		if (in_array($latHemisphere, [Coordinates::EAST, Coordinates::WEST])) {
			throw new InvalidLocationException(sprintf('Both coordinates "%s" are east-west hemisphere', $this->input));
		}
		if (in_array($lonHemisphere, [Coordinates::NORTH, Coordinates::SOUTH])) {
			throw new InvalidLocationException(sprintf('Both coordinates "%s" are north-south hemisphere', $this->input));
		}

		return new BetterLocation(
			$input,
			Coordinates::flip($latHemisphere) * $latCoord,
			Coordinates::flip($lonHemisphere) * $lonCoord,
			static::class,
		);
	}
}
