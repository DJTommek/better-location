<?php declare(strict_types=1);

namespace App\Geocaching;

use App\Config;
use App\Geocaching\Types\GeocachePreviewType;
use App\Utils\General;

class Client
{
	const LINK = 'https://www.geocaching.com';
	const LINK_CACHE = self::LINK . '/geocache/';
	const LINK_CACHE_API = self::LINK . '/api/proxy/web/search/geocachepreview/';

	const COOKIE_NAME = 'gspkauth';

	/** @var string */
	private $cookieToken;

	public function __construct(string $cookieToken)
	{
		$this->cookieToken = $cookieToken;
	}

	public function loadGeocachePreview(string $cacheId): GeocachePreviewType
	{
		$json = $this->makeJsonRequest(self::LINK_CACHE_API . $cacheId);
		if (isset($json->statusCode) && $json->statusCode !== 200) {
			throw new \Exception('Loading geocache preview responded with bad response code %d: "%s"', $json->statusCode, $json->statusMessage);
		}
		return GeocachePreviewType::createFromVariable($json);
	}

	private function makeJsonRequest(string $url): \stdClass
	{
		$response = $this->makeRequest($url);
		return json_decode($response, false, 512, JSON_THROW_ON_ERROR);
	}

	private function makeRequest(string $url): string
	{
		$cookies = [
			'gspkauth' => Config::GEOCACHING_COOKIE,
		];
		return General::fileGetContents($url, [
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 5,
			CURLOPT_COOKIE => http_build_query($cookies),
		]);
	}
}
