<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use Utils\MGRS;

final class USNGService extends AbstractService
{
	const NAME = 'USNG';

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

	/**
	 * @param $text
	 * @return BetterLocationCollection
	 */
	public static function findInText($text): BetterLocationCollection {
		$collection = new BetterLocationCollection();
		$inStringRegex = '/' . MGRS::getUSNGRegex(3, false, false) . '/';
		if (preg_match_all($inStringRegex, $text, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				try {
					$collection[] = self::parseCoords($matches[0][$i]);
				} catch (InvalidLocationException $exception) {
					$collection[] = $exception;
				}
			}
		}
		return $collection;
	}

	public static function isValid(string $input): bool {
		return MGRS::isMGRS($input);
	}

	/**
	 * @param string $input
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $input): BetterLocation {
		$mgrs = MGRS::fromUSNG($input);
		return new BetterLocation($input, $mgrs->getLat(), $mgrs->getLon(), get_called_class());
	}
}
