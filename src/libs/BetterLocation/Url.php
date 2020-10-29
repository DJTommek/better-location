<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Utils\General;

class Url
{
	const SHORT_URL_DOMAINS = [
		'bit.ly', // https://bitly.com/
		'tinyurl.com', // https://tinyurl.com/
		't.co', // https://help.twitter.com/en/using-twitter/url-shortener
		'rb.gy', // https://rebrandly.com/
		'tiny.cc', // https://tiny.cc/
	];

	public static function isShortUrl($url)
	{
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
	public static function getRedirectUrl($url): ?string
	{
		$headers = General::getHeaders($url);
		return $headers['location'] ?? null;
	}
}
