<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use Tracy\Debugger;
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
				return self::parseUrl($newLocation);
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Mapy.cz short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			return self::parseUrl($url);
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
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseUrl(string $url): BetterLocation {
		$parsedUrl = parse_url(urldecode($url));
		if (!isset($parsedUrl['query'])) {
			throw new InvalidLocationException(sprintf('Unable to get query for Mapy.cz link "%s".', $url));
		}
		parse_str($parsedUrl['query'], $urlParams);
		if ($urlParams) {
			// Dummy server is enabled and MapyCZ URL has necessary parameters
			if (MAPY_CZ_DUMMY_SERVER_URL && isset($urlParams['pid']) && is_numeric($urlParams['pid']) && $urlParams['pid'] > 0) {
				$result = self::getCoordsFromPanoramaId(intval($urlParams['pid']));
				$result->setPrefixMessage(sprintf('<a href="%s">Mapy.cz Panorama</a>', $url));
				return $result;
			}
			if (MAPY_CZ_DUMMY_SERVER_URL && isset($urlParams['id']) && is_numeric($urlParams['id']) && $urlParams['id'] > 0 && isset($urlParams['source'])) {
				$result =  self::getCoordsFromPlaceId($urlParams['source'], intval($urlParams['id']));
				$result->setPrefixMessage(sprintf('<a href="%s">Mapy.cz Place</a>', $url));
				return $result;
			}
			// @TODO if numeric ID (not coordinates) is set and dummy NodeJS is disabled, fallback to coordinates and show warning, that result might be inaccurate
			// MapyCZ URL has ID in format of coordinates
			if (isset($urlParams['id']) && preg_match('/^(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)$/', $urlParams['id'], $matches)) {
				return new BetterLocation(
					floatval($matches[2]),
					floatval($matches[1]),
					sprintf('<a href="%s">Mapy.cz</a>', $url),
				);
			}
			if (isset($urlParams['ma_x']) && isset($urlParams['ma_y'])) {
				return new BetterLocation(
					floatval($urlParams['ma_y']),
					floatval($urlParams['ma_x']),
					sprintf('<a href="%s">Mapy.cz</a>', $url),
				);
			}
			if (isset($urlParams['x']) && isset($urlParams['y'])) {
				return new BetterLocation(
					floatval($urlParams['y']),
					floatval($urlParams['x']),
					sprintf('<a href="%s">Mapy.cz</a>', $url),
				);
			}
		}
		throw new InvalidLocationException('Unable to get valid location from Mapy.cz link');
	}

	/**
	 * @param string $input
	 * @return BetterLocation[]
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): array {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}

	/**
	 * @param string $source
	 * @param int $placeId
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	private static function getCoordsFromPlaceId(string $source, int $placeId): BetterLocation {
		$dummyMapyCzApiUrl = MAPY_CZ_DUMMY_SERVER_URL . '/poiagg?' . http_build_query([
				'source' => $source,
				'point' => $placeId,
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
			return new BetterLocation(
				$jsonResponse->result->poi->mark->lat,
				$jsonResponse->result->poi->mark->lon,
				'MapyCZ Place ID',
			);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get valid coordinates from place ID "%d".', $placeId));
		}
	}

	/**
	 * @param int $panoramaId
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	private static function getCoordsFromPanoramaId(int $panoramaId): BetterLocation {
		$dummyMapyCzApiUrl = MAPY_CZ_DUMMY_SERVER_URL . '/panorpc?' . http_build_query([
				'point' => $panoramaId,
			]);
		try {
			$response = General::fileGetContents($dummyMapyCzApiUrl, [
				CURLOPT_CONNECTTIMEOUT => MAPY_CZ_DUMMY_SERVER_TIMEOUT,
				CURLOPT_TIMEOUT => MAPY_CZ_DUMMY_SERVER_TIMEOUT,
			]);
			$jsonResponse = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
		} catch (\Exception $exception) {
			Debugger::log(sprintf('MapyCZ dummy server request: "%s", error: "%s"', $dummyMapyCzApiUrl, $exception->getMessage()), Debugger::ERROR);
			throw new InvalidLocationException('Unable to get coordinates from MapyCZ panorama ID, contact Admin for more info.');
		}
		if (isset($jsonResponse->result->near->mark->lat) && isset($jsonResponse->result->near->mark->lon)) {
			return new BetterLocation(
				$jsonResponse->result->near->mark->lat,
				$jsonResponse->result->near->mark->lon,
				'MapyCZ Panorama'
			);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get valid coordinates from panorama ID "%d".', $panoramaId));
		}
	}
}
