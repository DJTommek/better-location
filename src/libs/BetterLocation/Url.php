<?php

namespace BetterLocation;

use Utils\General;

class Url
{
	const SHORT_URL_DOMAINS = [
		'bit.ly',
		'tinyurl.com',
		't.co',
	];

	public static function isShortUrl($url) {
		$parsedUrl = General::parseUrl($url);
		if ($parsedUrl && isset($parsedUrl['host'])) {
			$host = mb_strtolower($parsedUrl['host']);
			if (in_array($host, self::SHORT_URL_DOMAINS)) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param $url
	 * @return mixed|null
	 * @throws \Exception
	 */
	public static function getRedirectUrl($url): ?string {
		$headers = General::getHeaders($url);
		return $headers['location'] ?? null;
	}
}
