<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\Utils\MGRS;

final class USNGService extends AbstractService
{
	const NAME = 'USNG';

	public static function findInText(string $text): BetterLocationCollection
	{
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

	public static function isValid(string $input): bool
	{
		return MGRS::isMGRS($input);
	}

	/** @throws InvalidLocationException */
	public static function parseCoords(string $input): BetterLocation
	{
		$mgrs = MGRS::fromUSNG($input);
		return new BetterLocation($input, $mgrs->getLat(), $mgrs->getLon(), get_called_class());
	}
}
