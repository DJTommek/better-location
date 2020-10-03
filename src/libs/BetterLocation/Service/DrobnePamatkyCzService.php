<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

final class DrobnePamatkyCzService extends AbstractService
{
	const NAME = 'DrobnePamatky.cz';

	const LINK = 'https://www.drobnepamatky.cz';

	const PATH_REGEX = '/^\/node\/([0-9]+)$/';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotSupportedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/blizko?km[latitude]=%1$f&km[longitude]=%2$f&km[search_distance]=5&km[search_units]=km', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool {
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation {
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($url, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from %s link %s.', self::NAME, $url));
		}
	}

	public static function isUrl(string $url): bool {
		$url = mb_strtolower($url);
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array($parsedUrl['host'], ['drobnepamatky.cz', 'www.drobnepamatky.cz']) &&
			isset($parsedUrl['path']) &&
			preg_match(self::PATH_REGEX, $parsedUrl['path'])
		);
	}

	public static function parseUrl(string $url): ?array {
		try {
			$response = General::fileGetContents($url, [
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 5,
			]);
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
		if (!preg_match('/<meta\s+name="geo\.position"\s*content="([0-9.]+);\s*([0-9.]+)\s*"/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
		return [
			floatval($matches[1]),
			floatval($matches[2]),
		];
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
