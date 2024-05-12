<?php declare(strict_types=1);

namespace App\Utils;

use App\Http\UserAgents;
use Nette\Utils\Json;
use Psr\Http\Client\ClientInterface;
use Psr\SimpleCache\CacheInterface;

/**
 * Helper to quickly and easily run simple HTTP requests.
 *
 * Features:
 * - optional caching
 * - optional randomizing user-agents
 *
 * For more complex requests use ClientInterface instead.
 */
class Requestor
{
	public function __construct(
		private readonly ClientInterface $httpClient,
		private readonly CacheInterface $cache,
	) {
	}

	/**
	 * @return string Empty string in case of error (including HTTP errors 4xx)
	 */
	public function get(
		\Nette\Http\Url|\Nette\Http\UrlImmutable|string $url,
		int $cacheTtl = null,
		bool $randommizeUserAgent = true,
	): string {
		$urlString = (string)$url;

		if ($cacheTtl !== null) {
			$cacheKey = md5(self::class . $urlString);
			$cacheResult = $this->cache->get($cacheKey);
			if ($cacheResult !== null) {
				return $cacheResult;
			}
		}

		$headers = [];
		if ($randommizeUserAgent) {
			$headers['user-agent'] = UserAgents::getRandom();
		}

		$request = new \GuzzleHttp\Psr7\Request('GET', $urlString, $headers);
		$response = $this->httpClient->sendRequest($request);
		$body = (string)$response->getBody();

		if ($cacheTtl !== null) {
			$this->cache->set($cacheKey, $body, $cacheTtl);
		}

		return $body;
	}

	/**
	 * @return mixed Returns null if error occure, including HTTP errors 4xx
	 */
	public function getJson(
		\Nette\Http\Url|\Nette\Http\UrlImmutable|string $url,
		?int $cacheTtl = null,
	): mixed {
		$body = $this->get($url, $cacheTtl);
		if ($body === '') {
			return null;
		}

		return Json::decode($body);
	}
}
