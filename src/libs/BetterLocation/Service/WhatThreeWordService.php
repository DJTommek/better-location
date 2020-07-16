<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use \BetterLocation\BetterLocation;
use \BetterLocation\Service\Exceptions\BadWordsException;
use \BetterLocation\Service\Exceptions\InvalidApiKeyException;
use \Utils\General;

final class WhatThreeWordService extends AbstractService
{
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
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		throw new \InvalidArgumentException('Link is not implemented.');
	}

	public static function isValid(string $input): bool {
		return self::isWords($input) || self::isShortUrl($input) || self::isNormalUrl($input);
	}

	/**
	 * @param string $input words or URL
	 * @return BetterLocation
	 * @throws \Exception
	 */
	public static function parseCoords(string $input) {
		$words = null;
		if (self::isNormalUrl($input)) {
			$words = str_replace(self::LINK, '', $input);
		} else if (self::isShortUrl($input)) {
			$words = str_replace(self::LINK_SHORT, '', $input);
		} else if (self::isWords($input)) {
			$words = $input;
		}
		if ($words) {
			$apiLink = sprintf('https://api.what3words.com/v3/convert-to-coordinates?key=%s&words=%s&format=json', W3W_API_KEY, urlencode($words));
			$data = json_decode(General::fileGetContents($apiLink), false, 512, JSON_THROW_ON_ERROR);
			if (isset($data->error)) {
				dump($data->error);
				if ($data->error->code === 'BadWords') {
					throw new BadWordsException(sprintf('What3Words "%s" are not valid words: "%s"', urlencode($words), $data->error->message));
				} else if ($data->error->code === 'InvalidKey') {
					throw new InvalidApiKeyException($data->error->message);
				} else {
					throw new \Exception(sprintf('Detected What3Words "%s" but unable to get coordinates, invalid response from API: "%s"', urlencode($words), $data->error->message));
				}
			}
			return new BetterLocation(
				$data->coordinates->lat,
				$data->coordinates->lng,
				sprintf('(<a href="%s">W3W</a>: <code>%s</code>)', $data->map, $data->words),
			);
		} else {
			throw new \Exception('Unable to get coords from What3Words.');
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
}
