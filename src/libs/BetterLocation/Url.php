<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Utils\Strict;
use Nette\Http\UrlImmutable;

class Url
{
	const SHORT_URL_DOMAINS = [
		'bit.ly', // https://bitly.com/
		'tinyurl.com', // https://tinyurl.com/
		't.co', // https://help.twitter.com/en/using-twitter/url-shortener
		'rb.gy', // https://rebrandly.com/
		'tiny.cc', // https://tiny.cc/
		'4sq.com', // https://foursquare.com/
		'1url.cz', // https://1url.cz/
		'ow.ly', // http://ow.ly/
		'buff.ly', // https://buff.ly/
		'cutt.ly', // https://cutt.ly/
	];

	public static function isShortUrl(string|\Nette\Http\Url|UrlImmutable $url): bool
	{
		if (Strict::isUrl($url) === false) {
			return false;
		}
		$parsedUrl = Strict::url($url);
		$domainLower = mb_strtolower($parsedUrl->getDomain());
		return in_array($domainLower, self::SHORT_URL_DOMAINS, true);
	}
}
