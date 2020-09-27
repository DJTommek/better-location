<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Url;

final class WazeService extends AbstractService
{
	const NAME = 'Waze';

	const LINK = 'https://www.waze.com';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			return sprintf(self::LINK . '/ul?ll=%1$f,%2$f&navigate=yes', $lat, $lon);
		} else {
			return sprintf(self::LINK . '/ul?ll=%1$f,%2$f', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool {
		return self::isShortUrl($url) || self::isNormalUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws \Exception
	 */
	public static function parseCoords(string $url): BetterLocation {
		if (self::isShortUrl($url)) {
			$wazeUpdatedUrl = str_replace('waze.com/ul/h', 'www.waze.com/livemap?h=', $url);
			$newLocation = Url::getRedirectUrl($wazeUpdatedUrl);
			if ($newLocation) {
				// location is returned without domain
				$newLocation = self::LINK . $newLocation;
				$coords = self::parseUrl($newLocation);
				if ($coords) {
					return new BetterLocation($url, $coords[0], $coords[1], self::class);
				} else {
					throw new InvalidLocationException(sprintf('Unable to get coords for Waze short link "%s".', $url));
				}
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Waze short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($url, $coords[0], $coords[1], self::class);
			} else {
				throw new InvalidLocationException(sprintf('Unable to get coords for Waze normal link "%s".', $url));
			}
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for Waze link "%".', $url));
		}
	}

	public static function isShortUrl(string $url): bool {
		// Mapy.cz short link:
		// https://waze.com/ul/hu2fk8zezt
		return !!(preg_match('/^https?:\/\/waze\.com\/ul\/[a-z0-9A-Z]+$/', $url));
	}

	public static function isNormalUrl(string $url): bool {
		return !!(preg_match('/^https?:\/\/(?:www\.)?waze\.com\/.+/', $url));
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	public static function parseUrl(string $url): ?array {
		$paramsString = explode('?', $url);
		parse_str($paramsString[1], $params);
		if (isset($params['latlng'])) {
			$coords = explode(',', $params['latlng']);
		} else if (isset($params['ll'])) {
			$coords = explode(',', $params['ll']);
		} else if (isset($params['to'])) {
			$coords = explode(',', $params['to']);
			$coords[0] = str_replace('ll.', '', $coords[0]);
		} else {
			return null;
		}
		return [
			floatval($coords[0]),
			floatval($coords[1]),
		];
	}

	/**
	 * @param string $input
	 * @return BetterLocation[]
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
