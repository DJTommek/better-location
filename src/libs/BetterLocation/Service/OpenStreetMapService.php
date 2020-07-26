<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;

final class OpenStreetMapService extends AbstractService
{
	const LINK = 'https://www.openstreetmap.org/search?whereami=1&query=%1$f,%2$f&mlat=%1$f&mlon=%2$f#map=17/%1$f/%2$f';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new \InvalidArgumentException('Drive link is not implemented.');
		} else {
			return sprintf(self::LINK, $lat, $lon);
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
			throw new InvalidLocationException('Short URL processing is not yet implemented.');
		} else if (self::isNormalUrl($url)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">(OSM)</a>: ', $url));
			} else {
				throw new InvalidLocationException(sprintf('Unable to get coords from OSM basic link "%s".', $url));
			}
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from OSM link "%s".', $url));
		}
	}

	public static function isShortUrl(string $url): bool {
		// https://osm.org/go/0J0kf83sQ--?m=
		// https://osm.org/go/0EEQjE==
		// https://osm.org/go/0EEQjEEb
		// https://osm.org/go/0J0kf3lAU--
		// https://osm.org/go/0J0kf3lAU--?m=
		$openStreetMapShortUrl = 'https://osm.org/go/';
		return (substr($url, 0, mb_strlen($openStreetMapShortUrl)) === $openStreetMapShortUrl);
	}

	/**
	 * @TODO this method should be more strict
	 *
	 * @param string $url
	 * @return bool
	 *
	 */
	public static function isNormalUrl(string $url): bool {
		// https://www.openstreetmap.org/#map=17/49.355164/14.272819
		// https://www.openstreetmap.org/#map=17/49.32085/14.16402&layers=N
		// https://www.openstreetmap.org/#map=18/50.05215/14.45283
		// https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283
		// https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640
		$openStreetMapUrl = 'https://www.openstreetmap.org/';
		return (substr($url, 0, mb_strlen($openStreetMapUrl)) === $openStreetMapUrl);
	}

	/**
	 * @TODO query parameters should have higher priority than hash params
	 *
	 * @param string $url
	 * @return array|null
	 */
	public static function parseUrl(string $url): ?array {
		$paramsHashString = explode('#map=', $url);
		// url is in format some-url/blahblah#map=lat/lon
		if (count($paramsHashString) === 2) {
			$urlCoords = explode('/', $paramsHashString[1]);
			return [
				floatval($urlCoords[1]),
				floatval($urlCoords[2]),
			];
		} else {
			$paramsQueryString = explode('?', $url);
			if (count($paramsQueryString) === 2) {
				parse_str($paramsQueryString[1], $params);
				return [
					floatval($params['mlat']),
					floatval($params['mlon']),
				];
			}
		}
		return null;
	}

	/**
	 * @param string $input
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocation {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
