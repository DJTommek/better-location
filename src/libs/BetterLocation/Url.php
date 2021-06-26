<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Utils\General;

class Url
{
	/**
	 * List of content types for images supporting EXIF
	 *
	 * @see https://www.iana.org/assignments/media-types/media-types.xhtml#image
	 */
	const CONTENT_TYPE_IMAGE_EXIF = [
		'image/jpeg',
		'image/png',
		'image/tiff',
		'image/tiff-x',
		'image/webp',
	];

	const SHORT_URL_DOMAINS = [
		'bit.ly', // https://bitly.com/
		'tinyurl.com', // https://tinyurl.com/
		't.co', // https://help.twitter.com/en/using-twitter/url-shortener
		'rb.gy', // https://rebrandly.com/
		'tiny.cc', // https://tiny.cc/
		'4sq.com', // https://foursquare.com/
		'jdem.cz', // http://jdem.cz/
		'1url.cz', // https://1url.cz/
		'ow.ly', // http://ow.ly/
		'buff.ly', // https://buff.ly/
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
}
