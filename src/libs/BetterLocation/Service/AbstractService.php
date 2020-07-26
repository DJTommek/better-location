<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use Utils\General;

abstract class AbstractService
{
	abstract public static function getLink(float $lat, float $lon, bool $drive = false);

	abstract public static function isValid(string $input);

	abstract public static function parseCoords(string $input): BetterLocation;

	abstract public static function parseCoordsMultiple(string $input): BetterLocation;

	/**
	 * @param $url
	 * @return mixed|null
	 * @throws \Exception
	 */
	protected static function getRedirectUrl($url) {
		$headers = General::getHeaders($url);
		return $headers['location'] ?? null;
	}
}
