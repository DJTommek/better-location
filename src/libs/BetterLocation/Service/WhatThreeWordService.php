<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use \BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use \BetterLocation\Service\Exceptions\InvalidApiKeyException;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use What3words\Geocoder\Geocoder;

final class WhatThreeWordService extends AbstractService
{
	const NAME = 'W3W';

	const LINK = 'https://what3words.com/';
	const LINK_SHORT = 'https://w3w.co/';

	// https://developer.what3words.com/tutorial/detecting-if-text-is-in-the-format-of-a-3-word-address/
	const RE = '/^\/{3}(?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,}$/u';
	const RE_IN_STRING = '/\/{3}((?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,})/u';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws \Exception
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$w3wApi = new Geocoder(\Config::W3W_API_KEY);
			// @TODO dirty hack to get stdclass instead of associated array
			$response = $w3wApi->convertTo3wa($lat, $lon);
			$error = $w3wApi->getError();
			if ($error) {
				// @TODO dirty hack to get stdclass instead of associated array
				$error = json_decode(json_encode($error));
				throw new \Exception(sprintf('Unable to get W3W from coordinates: %s', $error->message));
			}
			$data = json_decode(json_encode($response));
			return $data->map;
		}
	}

	public static function isValid(string $input): bool {
		return self::isWords($input) || self::isShortUrl($input) || self::isNormalUrl($input);
	}

	/**
	 * @param string $input words or URL
	 * @return BetterLocation
	 * @throws \Exception
	 */
	public static function parseCoords(string $input): BetterLocation {
		$words = null;
		if (self::isNormalUrl($input)) {
			$words = str_replace(self::LINK, '', $input);
		} else if (self::isShortUrl($input)) {
			$words = str_replace(self::LINK_SHORT, '', $input);
		} else if (self::isWords($input)) {
			$words = $input;
		}
		if ($words) {
			$w3wApi = new Geocoder(\Config::W3W_API_KEY);
			$response = $w3wApi->convertToCoordinates($words);
			$error = $w3wApi->getError();
			if ($error) {
				// @TODO dirty hack to get stdclass instead of associated array
				$error = json_decode(json_encode($error));
				if ($error->code === 'BadWords') {
					throw new InvalidLocationException(sprintf('What3Words "%s" are not valid words: "%s"', $words, $error->message));
				} else if ($error->code === 'InvalidKey') {
					throw new InvalidApiKeyException(sprintf('What3Words API responded with error: "%s"', $error->message));
				} else {
					throw new InvalidLocationException(sprintf('Detected What3Words "%s" but unable to get coordinates, invalid response from API: "%s"', $words, $error->message));
				}
			} else {
				// @TODO dirty hack to get stdclass instead of associated array
				$data = json_decode(json_encode($response));
				$betterLocation = new BetterLocation($input, $data->coordinates->lat, $data->coordinates->lng, self::class);
				$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a>: <code>%s</code>', $data->map, self::NAME, $data->words));
				return $betterLocation;
			}
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from What3Words "%s".', $input));
		}
	}

	public static function isWords(string $words): bool {
		// ///chladná.naopak.vložit
		// \\\flicks.gazed.tapes
		return !!(preg_match_all(self::RE, $words));
	}

	public static function isShortUrl(string $url): bool {
		// https://w3w.co/chladná.naopak.vložit
		return (substr($url, 0, mb_strlen(self::LINK_SHORT)) === self::LINK_SHORT);
	}

	public static function isNormalUrl(string $url): bool {
		// https://what3words.com/define.readings.cucumber
		return (substr($url, 0, mb_strlen(self::LINK)) === self::LINK);
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
