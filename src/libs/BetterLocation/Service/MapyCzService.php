<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Url;
use MapyCzApi\MapyCzApi;
use Tracy\Debugger;
use Utils\General;

final class MapyCzService extends AbstractService
{
	const NAME = 'Mapy.cz';
	const LINK = 'https://mapy.cz/zakladni?y=%1$f&x=%2$f&source=coor&id=%2$f%%2C%1$f';

	const TYPE_UNKNOWN = 'unknown';
	const TYPE_MAP = 'Map center';
	const TYPE_PLACE_ID = 'Place';
	const TYPE_PLACE_COORDS = 'Place coords';
	const TYPE_PANORAMA = 'Panorama';

	public static function getConstants(): array {
		return [
			self::TYPE_PANORAMA,
			self::TYPE_PLACE_ID,
			self::TYPE_PLACE_COORDS,
			self::TYPE_MAP,
			self::TYPE_UNKNOWN,
		];
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			// No official API for backend so it might be probably generated only via simulating frontend
			// @see https://napoveda.seznam.cz/forum/threads/120687/1
			// @see https://napoveda.seznam.cz/forum/file/13641/Schema-otevirani-aplikaci-z-url-a-externe.pdf
			throw new NotImplementedException('Drive link is not implemented.');
		} else {
			return sprintf(self::LINK, $lat, $lon);
		}
	}

	public static function getScreenshotLink(float $lat, float $lon): string {
		// URL Parameters to screenshoter (Mapy.cz website is using it with p=3 and l=0):
		// l=0 hide right panel (can be opened via arrow icon)
		// p=1 disable right panel (can't be opened) and disable bottom left panorama view screenshot
		// p=2 show right panel and (can't be hidden) and disable bottom left panorama view screenshot
		// p=3 disable right panel (can't be opened) and enable bottom left panorama view screenshot
		return 'https://en.mapy.cz/screenshoter?url=' . urlencode(self::getLink($lat, $lon) . '&p=3&l=0');
	}

	public static function isValid(string $url): bool {
		return self::isShortUrl($url) || self::isNormalUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public static function parseCoords(string $url): BetterLocation {
		return self::parseCoordsHelper($url, false);
	}

	/**
	 * @param string $url
	 * @return BetterLocationCollection
	 * @throws InvalidLocationException
	 */
	public static function parseCoordsMultiple(string $url): BetterLocationCollection {
		return self::parseCoordsHelper($url, true);
	}

	/**
	 * @param string $url
	 * @param bool $returnCollection
	 * @return BetterLocation|BetterLocationCollection
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public static function parseCoordsHelper(string $url, bool $returnCollection) {
		if (self::isShortUrl($url)) {
			$newLocation = Url::getRedirectUrl($url);
			if ($newLocation) {
				return self::parseUrl($newLocation, $returnCollection);
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Mapy.cz short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			return self::parseUrl($url, $returnCollection);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for Mapy.cz link "%s".', $url));
		}
	}

	public static function isShortUrl(string $url): bool {
		// Mapy.cz short link:
		// https://mapy.cz/s/porumejene
		// https://en.mapy.cz/s/porumejene
		// https://en.mapy.cz/s/3ql7u
		// https://en.mapy.cz/s/faretabotu
		$parsedUrl = parse_url($url);
		return (
			$parsedUrl &&
			isset($parsedUrl['host']) &&
			strpos($parsedUrl['host'], 'mapy.cz') !== false &&
			isset($parsedUrl['path']) &&
			preg_match('/^\/s\/[a-zA-Z0-9]+$/', $parsedUrl['path'])
		);
	}

	public static function isNormalUrl(string $url): bool {
		// https://en.mapy.cz/zakladni?x=14.2991869&y=49.0999235&z=16&pano=1&source=firm&id=350556
		// https://mapy.cz/?x=15.278244&y=49.691235&z=15&ma_x=15.278244&ma_y=49.691235&ma_t=Jsem+tady%2C+otev%C5%99i+odkaz&source=coor&id=15.278244%2C49.691235
		// Mapy.cz panorama:
		// https://en.mapy.cz/zakladni?x=14.3139613&y=49.1487367&z=15&pano=1&pid=30158941&yaw=1.813&fov=1.257&pitch=-0.026
		$parsedUrl = parse_url(urldecode($url));
		if (isset($parsedUrl['query']) && strpos($parsedUrl['host'], 'mapy.cz') !== false) {
			parse_str($parsedUrl['query'], $urlParams);
			return (
				isset($urlParams['x']) && isset($urlParams['y']) || // map position
				isset($urlParams['id']) && isset($urlParams['source']) || // place ID
				isset($urlParams['pid']) || // panorama ID
				isset($urlParams['ma_x']) && isset($urlParams['ma_y']) // not sure what is this...
			);
		}
		return false;
	}

	/**
	 * @param string $url
	 * @param bool $returnCollection
	 * @return BetterLocation|BetterLocationCollection
	 * @throws InvalidLocationException
	 */
	public static function parseUrl(string $url, bool $returnCollection = false) {
		$betterLocationCollection = new BetterLocationCollection();
		$parsedUrl = parse_url(urldecode($url));
		if (!isset($parsedUrl['query'])) {
			throw new InvalidLocationException(sprintf('Unable to get query for Mapy.cz link "%s".', $url));
		}
		parse_str($parsedUrl['query'], $urlParams);
		if ($urlParams) {
			$mapyCzApi = new MapyCzApi();
			// URL with Panoraama ID
			if (isset($urlParams['pid']) && is_numeric($urlParams['pid']) && $urlParams['pid'] > 0) {
				$mapyCzResponse = $mapyCzApi->loadPanoramaDetails(intval($urlParams['pid']));
				$betterLocation = new BetterLocation($url, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PANORAMA);
				if ($returnCollection) {
					$betterLocationCollection[self::TYPE_PANORAMA] = $betterLocation;
				} else {
					return $betterLocation;
				}
			}
			// URL with Place ID
			if (isset($urlParams['id']) && is_numeric($urlParams['id']) && $urlParams['id'] > 0 && isset($urlParams['source'])) {
				$mapyCzResponse = $mapyCzApi->loadPoiDetails($urlParams['source'], intval($urlParams['id']));
				$betterLocation = new BetterLocation($url, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PLACE_ID);
				if ($returnCollection) {
					$betterLocationCollection[self::TYPE_PLACE_ID] = $betterLocation;
				} else {
					return $betterLocation;
				}
			}
			// @TODO if numeric ID (not coordinates) is set and dummy NodeJS is disabled, fallback to coordinates and show warning, that result might be inaccurate
			// MapyCZ URL has ID in format of coordinates
			if (isset($urlParams['id']) && preg_match('/^(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)$/', $urlParams['id'], $matches)) {
				$betterLocation = new BetterLocation($url, floatval($matches[2]), floatval($matches[1]), self::class, self::TYPE_PLACE_COORDS);
				if ($returnCollection) {
					$betterLocationCollection[] = $betterLocation;
				} else {
					return $betterLocation;
				}
			}
			if (isset($urlParams['ma_x']) && isset($urlParams['ma_y'])) {
				$betterLocation = new BetterLocation($url, floatval($urlParams['ma_y']), floatval($urlParams['ma_x']), self::class, self::TYPE_UNKNOWN);
				if ($returnCollection) {
					$betterLocationCollection[] = $betterLocation;
				} else {
					return $betterLocation;
				}
			}
			if (isset($urlParams['x']) && isset($urlParams['y'])) {
				$betterLocation = new BetterLocation($url, floatval($urlParams['y']), floatval($urlParams['x']), self::class, self::TYPE_MAP);
				if ($returnCollection) {
					$betterLocationCollection[] = $betterLocation;
				} else {
					return $betterLocation;
				}
			}
		}
		if ($returnCollection && count($betterLocationCollection) > 0) {
			return $betterLocationCollection;
		}
		throw new InvalidLocationException('Unable to get any valid location from Mapy.cz link');
	}
}
