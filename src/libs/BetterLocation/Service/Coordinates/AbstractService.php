<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

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

	abstract public static function parseCoords(string $input);

	abstract public static function findInText(string $text): array;

}
