<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;

final class WazeService extends AbstractService
{
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
	public static function parseCoords(string $url) {
		if (self::isShortUrl($url)) {
			$wazeUpdatedUrl = str_replace('waze.com/ul/h', 'www.waze.com/livemap?h=', $url);
			$newLocation = self::getRedirectUrl($wazeUpdatedUrl);
			if ($newLocation) {
				// location is returned without domain
				$newLocation = self::LINK . $newLocation;
				$coords = self::parseUrl($newLocation);
				if ($coords) {
					return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Waze</a>', $url));
				} else {
					throw new InvalidLocationException(sprintf('Unable to get coords for Waze short link "%s".', $url));
				}
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Waze short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Waze</a>', $url));
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
		return !!(preg_match('/https:\/\/waze\.com\/ul\/[a-z0-9A-Z]+$/', $url));
	}

	public static function isNormalUrl(string $url): bool {
		// https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15
		// https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes
		// https://www.waze.com/ul?ll=50.06300713%2C14.43964005
		//
		// https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website
		// https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016
		//
		// https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842
		// https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842
		// https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363
		return substr($url, 0, mb_strlen(self::LINK)) === self::LINK;
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
}
