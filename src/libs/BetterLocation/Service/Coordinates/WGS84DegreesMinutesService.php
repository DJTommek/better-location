<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;

final class WGS84DegreesMinutesService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3})[° ]{1,3}([0-9]{1,3}\.[0-9]{1,20}) ?\'?';
	const NAME = 'WGS84 DM';

	public static function parseCoords(string $input): BetterLocation
	{
		if (!preg_match('/^' . static::getRegex() . '$/u', $input, $matches)) {
			throw new InvalidLocationException(sprintf('Input is not valid %s coordinates.', self::NAME));
		}
		// preg_match truncating empty values from the end in $matches array: https://stackoverflow.com/questions/43912763/php-can-preg-match-include-unmatched-groups#comment74860670_43912763
		$matches = array_pad($matches, 9, '');
		return static::processWGS84(self::class, $matches);
	}
}
