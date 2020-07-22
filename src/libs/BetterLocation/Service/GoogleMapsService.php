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

	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		return sprintf($drive ? self::LINK_DRIVE : self::LINK, $lat, $lon);
	}

	public static function isValid(string $url): bool {
		return self::isShortUrl($url) || self::isNormalUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation {
		if (self::isShortUrl($url)) {
			// Google maps short link:
			// https://goo.gl/maps/rgZZt125tpvf2rnCA
			// https://goo.gl/maps/eUYMwABdpv9NNSDX7
			// https://goo.gl/maps/hEbUKxSuMjA2
			// https://goo.gl/maps/pPZ91TfW2edvejbb6
			//
			// https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9
			// https://maps.app.goo.gl/nJqTbFow1HtofApTA

			$newLocation = self::getRedirectUrl($url);
			$coords = self::parseUrl($newLocation);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Goo.gl</a>', $url));
			} else {
				throw new InvalidLocationException(sprintf('Unable to get coords for Goo.gl link "%s".', $url));
			}
		} else if (self::isNormalUrl($url)) {
			// Google maps normal links:
			// https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da
			// https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488
			// https://www.google.cz/maps/@36.8264601,22.5287146,9.33z
			// https://www.google.cz/maps/place/49%C2%B020'00.6%22N+14%C2%B017'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174
			// https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894
			// https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022
			$coords = self::parseUrl($url);
			if ($coords) {
				return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Google</a>', $url));
			} else {
				throw new InvalidLocationException(sprintf('Unable to get coords for Google maps normal link "%s".', $url));
			}
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for Google maps link "%s".', $url));
		}
	}

	public static function isShortUrl(string $url): bool {
		// Google maps short link: https://goo.gl/maps/rgZZt125tpvf2rnCA
		// https://goo.gl/maps/eUYMwABdpv9NNSDX7
		// https://goo.gl/maps/hEbUKxSuMjA2
		// https://goo.gl/maps/pPZ91TfW2edvejbb6
		//
		// https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9 @TODO not working, maybe needs to be added request header "Host"?
		// https://maps.app.goo.gl/nJqTbFow1HtofApTA @TODO not working, maybe needs to be added request header "Host"?
		$googleMapsShortUrlV1 = 'https://goo.gl/maps/';
		$googleMapsShortUrlV2 = 'https://maps.app.goo.gl/';
		return (
			substr($url, 0, mb_strlen($googleMapsShortUrlV1)) === $googleMapsShortUrlV1 ||
			substr($url, 0, mb_strlen($googleMapsShortUrlV2)) === $googleMapsShortUrlV2
		);
	}

	public static function isNormalUrl(string $url): bool {
		// Google maps normal links:
		// https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da
		// https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488
		// https://www.google.cz/maps/@36.8264601,22.5287146,9.33z
		// https://www.google.cz/maps/place/49%C2%B020'00.6%22N+14%C2%B017'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174
		// https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894
		// https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022
		return !!(preg_match('/https:\/\/(?:(?:www|maps)\.)google\.[a-z]{1,5}\//', $url));
	}

	/**
	 * @param string $url
	 * @return array|null
	 * @throws InvalidLocationException
	 */
	public static function parseUrl(string $url): ?array {
		$paramsString = explode('?', $url);
		if (count($paramsString) === 2) {
			parse_str($paramsString[1], $params);
		}
		// https://www.google.com/maps/place/50%C2%B006'04.6%22N+14%C2%B031'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1
		// Regex is matching "!3d50.1012711!4d14.5288824"
		if (preg_match('/!3d(-?[0-9]{1,3}\.[0-9]+)!4d(-?[0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			return [
				floatval($matches[1]),
				floatval($matches[2]),
			];
		} else if (isset($params['ll'])) {
			$coords = explode(',', $params['ll']);
			return [
				floatval($coords[0]),
				floatval($coords[1]),
			];
		} else if (isset($params['q'])) { // @TODO in this parameter probably might be also non-coordinates locations (eg. address)
			$coords = explode(',', $params['q']);
			if (count($coords) !== 2) {
				throw new InvalidLocationException(sprintf('Invalid "q" parameter in Google link "%s".', $url));
			}
			return [
				floatval($coords[0]),
				floatval($coords[1]),
			];
			// Warning: coordinates in URL in format "@50.00,15.00" is position of the map, not selected/shared point.
		} else if (preg_match('/@([0-9]{1,3}\.[0-9]+),([0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			return [
				floatval($matches[1]),
				floatval($matches[2]),
			];
		} else {
			// URL don't have any coordinates or place-id to translate so load content and there are some coordinates hidden in page in some of brutal multi-array
			$content = General::fileGetContents($url);
			// Regex is searching for something like this: ',"",null,[null,null,50.0641584,14.468139599999999]';
			// Warning: Not exact position
			if (preg_match('/","",null,\[null,null,(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)]\n/', $content, $matches)) {
				return [
					floatval($matches[1]),
					floatval($matches[2]),
				];
			}
		}
		return null;
	}
}
