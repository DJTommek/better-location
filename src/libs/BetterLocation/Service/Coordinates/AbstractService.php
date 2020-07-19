<?php

declare(strict_types=1);

namespace BetterLocation\Service\Coordinates;

use Utils\General;

abstract class AbstractService extends \BetterLocation\Service\AbstractService
{
	const RE_HEMISPHERE = ' ?([-+NSWE])? ?';
	const RE_SPACE = '[., ]{1,4}';

	abstract public static function getLink(float $lat, float $lon, bool $drive = false);

	abstract public static function isValid(string $input);

	abstract public static function parseCoords(string $input);

	abstract public static function findInText(string $text): array;

}
