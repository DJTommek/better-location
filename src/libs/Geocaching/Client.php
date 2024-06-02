<?php declare(strict_types=1);

namespace App\Geocaching;

use App\Geocaching\Types\GeocachePreviewType;
use App\Utils\Requestor;

class Client
{
	const LINK = 'https://www.geocaching.com';
	const LINK_CACHE = self::LINK . '/geocache/';
	const LINK_CACHE_API = self::LINK . '/api/proxy/web/search/geocachepreview/';
	const LINK_SHARE = 'https://coord.info';

	private const LOGIN_COOKIE_NAME = 'gspkauth';

	public function __construct(
		private readonly Requestor $requestor,
		private readonly string $cookieToken,
		private readonly ?int $cacheTtl = null,
	) {
	}

	public function loadGeocachePreview(string $cacheId): GeocachePreviewType
	{
		$json = $this->makeJsonRequest(self::LINK_CACHE_API . $cacheId);
		if (isset($json->statusCode) && $json->statusCode !== 200) {
			throw new \Exception(sprintf('Loading geocache preview responded with bad response code %d: "%s"', $json->statusCode, $json->statusMessage));
		}
		return GeocachePreviewType::createFromVariable($json);
	}

	private function makeJsonRequest(string $url): \stdClass
	{
		$headers = [
			'Cookie' => sprintf('%s=%s', self::LOGIN_COOKIE_NAME, $this->cookieToken),
		];

		return $this->requestor->getJson($url, $this->cacheTtl, headers: $headers);
	}
}
