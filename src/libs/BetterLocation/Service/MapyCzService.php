<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;

final class MapyCzService extends AbstractService
{
	const LINK = 'https://en.mapy.cz/zakladni?y=%1$f&x=%2$f&source=coor&id=%2$f%%2C%1$f';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			return sprintf(self::LINK, $lat, $lon);
		} else {
			// No official API for backend so it might be probably generated only via simulating frontend
			// @see https://napoveda.seznam.cz/forum/threads/120687/1
			// @see https://napoveda.seznam.cz/forum/file/13641/Schema-otevirani-aplikaci-z-url-a-externe.pdf
			throw new \InvalidArgumentException('Drive link is not implemented.');
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
		dump($url);
		dump(self::isShortUrl($url));
		if (self::isShortUrl($url)) {
			$newLocation = self::getRedirectUrl($url);
			if ($newLocation) {
				$coords = self::parseUrl($newLocation);
				if ($coords) {
					return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">(Mapy.cz)</a>: ', $url));
				} else {
					throw new \Exception('Unable to get coords for Mapy.cz short link.');
				}
			} else {
				throw new \Exception('Unable to get real url for Mapy.cz short link.');
			}
		} else if (self::isNormalUrl($url)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">(Mapy.cz)</a>: ', $url));
			} else {
				throw new \Exception('Unable to get coords from Mapy.cz normal link.');
			}
		} else {
			throw new \Exception('Unable to get coords for Mapy.cz link.');
		}
	}

	public static function isShortUrl(string $url): bool {
		// Mapy.cz short link:
		// https://mapy.cz/s/porumejene
		// https://en.mapy.cz/s/porumejene
		// https://en.mapy.cz/s/3ql7u
		return !!(preg_match('/https:\/\/([a-z]{1,3}\.)?mapy\.cz\/s\/[a-z0-9A-Z]+/', $url));
	}

	public static function isNormalUrl(string $url): bool {
		// https://en.mapy.cz/zakladni?x=14.2991869&y=49.0999235&z=16&pano=1&source=firm&id=350556
		// https://mapy.cz/?x=15.278244&y=49.691235&z=15&ma_x=15.278244&ma_y=49.691235&ma_t=Jsem+tady%2C+otev%C5%99i+odkaz&source=coor&id=15.278244%2C49.691235
		// Mapy.cz panorama:
		// https://en.mapy.cz/zakladni?x=14.3139613&y=49.1487367&z=15&pano=1&pid=30158941&yaw=1.813&fov=1.257&pitch=-0.026
		return !!(preg_match('/https:\/\/([a-z]{1,3}\.)?mapy\.cz\/([a-z0-9-]{2,})?\?/', $url));
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	public static function parseUrl(string $url): ?array {
		$paramsString = explode('?', $url);
		parse_str($paramsString[1], $params);
		return [
			floatval($params['y']),
			floatval($params['x']),
		];
	}
}
