<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use \Utils\General;

final class GoogleMapsService extends AbstractService
{
	const LINK = 'https://www.google.cz/maps/place/%1$f,%2$f?q=%1$f,%2$f';
	const LINK_DRIVE = 'https://maps.google.cz/?daddr=%1$f,%2$f&travelmode=driving';

	const TYPE_UNKNOWN = 'unknown';
	const TYPE_MAP = 'Map center';
	const TYPE_PLACE = 'Place';
	const TYPE_STREET_VIEW = 'Street view';
	const TYPE_SEARCH = 'search';
	const TYPE_HIDDEN = 'hidden';

	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		return sprintf($drive ? self::LINK_DRIVE : self::LINK, $lat, $lon);
	}

	public static function isValid(string $url): bool {
		return self::isShortUrl($url) || self::isNormalUrl($url);
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @return string
	 * @throws \Exception
	 */
	public static function getScreenshotLink(float $lat, float $lon): string {
		if (defined('GOOGLE_MAPS_API_KEY') === false) {
			throw new \Exception('Google maps API key is not defined.');
		}
		$params = [
			'center' => '',
			'zoom' => '13',
			'size' => '600x600',
			'maptype' => 'roadmap',
			'markers' => sprintf('color:red|label:|%1$s,%2$s', $lat, $lon),
			'key' => GOOGLE_MAPS_API_KEY,
		];
		return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation {
		return self::parseCoordsHelper($url, false);
	}

	/**
	 * @param string $url
	 * @return array|BetterLocation[]
	 * @throws InvalidLocationException
	 */
	public static function parseCoordsMultiple(string $url): array {
		return self::parseCoordsHelper($url, true);
	}

	/**
	 * @param string $url
	 * @param bool $returnArray
	 * @return BetterLocation|BetterLocation[]
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public static function parseCoordsHelper(string $url, bool $returnArray) {
		if (self::isShortUrl($url)) {
			$newLocation = self::getRedirectUrl($url);
			if ($newLocation) {
				return self::parseUrl($newLocation, $returnArray);
			} else {
				throw new InvalidLocationException(sprintf('Unable to get real url for Goo.gl short link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {
			return self::parseUrl($url, $returnArray);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for Google maps link "%s".', $url));
		}
	}

	public static function isShortUrl(string $url): bool {
		$googleMapsShortUrlV1 = 'https://goo.gl/maps/';
		$googleMapsShortUrlV2 = 'https://maps.app.goo.gl/';
		return (
			substr($url, 0, mb_strlen($googleMapsShortUrlV1)) === $googleMapsShortUrlV1 ||
			substr($url, 0, mb_strlen($googleMapsShortUrlV2)) === $googleMapsShortUrlV2
		);
	}

	public static function isNormalUrl(string $url): bool {
		return !!(preg_match('/https:\/\/(?:(?:www|maps)\.)google\.[a-z]{1,5}\//', $url));
	}

	/**
	 * @param string $url
	 * @param bool $returnArray
	 * @return BetterLocation|BetterLocation[]
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	public static function parseUrl(string $url, bool $returnArray = false) {
		dump($url);
		$betterLocations = [];
		$paramsString = explode('?', $url);
		if (count($paramsString) === 2) {
			parse_str($paramsString[1], $params);
		}
		// https://www.google.com/maps/place/50%C2%B006'04.6%22N+14%C2%B031'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1
		// Regex is matching "!3d50.1012711!4d14.5288824"
		if (preg_match_all('/!3d(-?[0-9]{1,3}\.[0-9]+)!4d(-?[0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			/**
			 * There might be more than just one parameter to match, example:
			 * https://www.google.com/maps/place/49%C2%B050'19.5%22N+18%C2%B023'29.9%22E/@49.8387187,18.3912988,88m/data=!3m1!1e3!4m14!1m7!3m6!1s0x4713fdb643f28f71:0xcbeec5757ed37704!2zT2Rib3LFrywgNzM1IDQxIFBldMWZdmFsZA!3b1!8m2!3d49.8386455!4d18.39618!3m5!1s0x0:0x0!7e2!8m2!3d49.8387596!4d18.3916417
			 * In this case correct is the last one. If used "share button", it will generate this link https://goo.gl/maps/aTQGPSpepT2EDCrT8 which leads to:
			 * https://www.google.com/maps/place/49%C2%B050'19.5%22N+18%C2%B023'29.9%22E/@49.8387187,18.3912988,88m/data=!3m1!1e3!4m6!3m5!1s0x0:0x0!7e2!8m2!3d49.8387596!4d18.3916417?shorturl=1
			 * In this URL is only one parameter to match. Strange...
			 */
			$result = new BetterLocation(
				floatval(end($matches[1])),
				floatval(end($matches[2])),
				sprintf('<a href="%s">Google %s</a>', $url, self::TYPE_PLACE),
			);
			if ($returnArray) {
				$betterLocations[self::TYPE_PLACE] = $result;
			} else {
				return $result;
			}

		}
		if (isset($params['ll'])) {
			$coords = explode(',', $params['ll']);
			$result = new BetterLocation(
				floatval($coords[0]),
				floatval($coords[1]),
				sprintf('<a href="%s">Google %s</a>', $url, self::TYPE_UNKNOWN),
			);
			if ($returnArray) {
				$betterLocations[self::TYPE_UNKNOWN] = $result;
			} else {
				return $result;
			}
		}
		if (isset($params['q'])) { // @TODO in this parameter probably might be also non-coordinates locations (eg. address)
			$coords = explode(',', $params['q']);
			if (count($coords) !== 2) {
				throw new InvalidLocationException(sprintf('Invalid "q" parameter in Google link "%s".', $url));
			}
			$result = new BetterLocation(
				floatval($coords[0]),
				floatval($coords[1]),
				sprintf('<a href="%s">Google %s</a>', $url, self::TYPE_SEARCH),
			);
			if ($returnArray) {
				$betterLocations[self::TYPE_SEARCH] = $result;
			} else {
				return $result;
			}
			// Warning: coordinates in URL in format "@50.00,15.00" is position of the map, not selected/shared point.
		}
		if (preg_match('/@([0-9]{1,3}\.[0-9]+),([0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			if (
				preg_match('/,[0-9.]+a/', $url) &&
				preg_match('/,[0-9.]+y/', $url) &&
				preg_match('/,[0-9.]+h/', $url) &&
				preg_match('/,[0-9.]+t/', $url)
			) {
				$type = self::TYPE_STREET_VIEW;
			} else {
				$type = self::TYPE_MAP;
			}
			$result = new BetterLocation(
				floatval($matches[1]),
				floatval($matches[2]),
				sprintf('<a href="%s">Google %s</a>', $url, $type),
			);
			if ($returnArray) {
				$betterLocations[$type] = $result;
			} else {
				return $result;
			}
		}
		// To prevent doing unnecessary request, this is done only if there is no other location detected
		// Google is disabling access with RECAPTCHA
		// @TODO probably there will be always at least map center so this code never occure? Needs testing
		if ($returnArray === false || count($betterLocations) <= 0) {
			// URL don't have any coordinates or place-id to translate so load content and there are some coordinates hidden in page in some of brutal multi-array
			$content = General::fileGetContents($url);
			dump($content);
			// Regex is searching for something like this: ',"",null,[null,null,50.0641584,14.468139599999999]';
			// Warning: Not exact position
			if (preg_match('/","",null,\[null,null,(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)]\n/', $content, $matches)) {
				$result = new BetterLocation(
					floatval($matches[1]),
					floatval($matches[2]),
					sprintf('<a href="%s">Google %s</a>', $url, self::TYPE_HIDDEN),
				);
				if ($returnArray) {
					$betterLocations[self::TYPE_HIDDEN] = $result;
				} else {
					return $result;
				}
			}
		}
		if ($returnArray && count($betterLocations) > 0) {
			return $betterLocations;
		}
		throw new InvalidLocationException('Unable to get any valid location from Google link');
	}
}
