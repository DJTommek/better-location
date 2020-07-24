<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use Tracy\Debugger;
use Utils\Coordinates;
use Utils\General;

final class MapyCzService extends AbstractService
{
	const LINK = 'https://mapy.cz/zakladni?y=%1$f&x=%2$f&source=coor&id=%2$f%%2C%1$f';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			// No official API for backend so it might be probably generated only via simulating frontend
			// @see https://napoveda.seznam.cz/forum/threads/120687/1
			// @see https://napoveda.seznam.cz/forum/file/13641/Schema-otevirani-aplikaci-z-url-a-externe.pdf
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
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public static function parseCoords(string $url): BetterLocation {
		if (self::isShortUrl($url)) {
			$newLocation = self::getRedirectUrl($url);
			if ($newLocation) {
				$coords = self::parseUrl($newLocation);
				if ($coords) {
					return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Mapy.cz</a>', $url));
				} else {
					throw new InvalidLocationException(sprintf('Unable to get coords for Mapy.cz short link "%s".', $url));
				}
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Mapy.cz short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Mapy.cz</a>', $url));
			} else {
				throw new InvalidLocationException(sprintf('Unable to get coords from Mapy.cz normal link "%s".', $url));
			}
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
				isset($urlParams['x']) && isset($urlParams['y']) ||
				isset($urlParams['ma_x']) && isset($urlParams['ma_y']) ||
				isset($urlParams['id'])
			);
		}
		return false;
	}

	/**
	 * @param string $url
	 * @return array|null
	 * @throws InvalidLocationException
	 */
	public static function parseUrl(string $url): ?array {
		$parsedUrl = parse_url(urldecode($url));
		if (!isset($parsedUrl['query'])) {
			throw new InvalidLocationException(sprintf('Unable to get query for Mapy.cz link "%s".', $url));
		}
		parse_str($parsedUrl['query'], $urlParams);
		if ($urlParams) {
			if (isset($urlParams['id'])) {
				if (MAPY_CZ_DUMMY_SERVER_URL && is_numeric($urlParams['id']) && $urlParams['id'] > 0 && isset($urlParams['source'])) {
					$dummyMapyCzApiUrl = MAPY_CZ_DUMMY_SERVER_URL . '?' . http_build_query([
							'point' => $urlParams['id'],
							'source' => $urlParams['source'],
						]);
					try {
						$response = General::fileGetContents($dummyMapyCzApiUrl, [
							CURLOPT_CONNECTTIMEOUT => MAPY_CZ_DUMMY_SERVER_TIMEOUT,
							CURLOPT_TIMEOUT => MAPY_CZ_DUMMY_SERVER_TIMEOUT,
						]);
						$jsonResponse = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
					} catch (\Exception $exception) {
						Debugger::log(sprintf('MapyCZ dummy server request: "%s", error: "%s"', $dummyMapyCzApiUrl, $exception->getMessage()), Debugger::ERROR);
						throw new InvalidLocationException('Unable to get coordinates from MapyCZ place ID, contact Admin for more info.');
					}
					if (isset($jsonResponse->result->poi->mark->lat) && isset($jsonResponse->result->poi->mark->lon)) {
						return [
							$jsonResponse->result->poi->mark->lat,
							$jsonResponse->result->poi->mark->lon,
						];
					} else {
						throw new InvalidLocationException(sprintf('Unable to get valid coordinates from point ID "%s".', $urlParams['id']));
					}
				} else if (preg_match(Coordinates::RE_WGS84_DEGREES, $urlParams['id'], $matches)) {
					return [
						floatval($matches[5]),
						floatval($matches[2]),
					];
				} /** @noinspection PhpStatementHasEmptyBodyInspection */ else {
					// throw new InvalidLocationException(sprintf('Unable to get query for Mapy.cz link "%s", invalid id parameter.', $url));
					// @TODO if numeric ID (not coordinates) is set and dummy NodeJS is disabled, fallback to coordinates and show warning, that result might be inaccurate
				}
			}
			if (isset($urlParams['ma_x']) && isset($urlParams['ma_y'])) {
				return [
					floatval($urlParams['ma_y']),
					floatval($urlParams['ma_x']),
				];
			}
			if (isset($urlParams['x']) && isset($urlParams['y'])) {
				return [
					floatval($urlParams['y']),
					floatval($urlParams['x']),
				];
			}
		}
		return null;
	}
}
