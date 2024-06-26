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
	 * @param array<string, mixed> $headers
	 * @return string Empty string in case of error (including HTTP errors 4xx)
	 */
	public function get(
		\Nette\Http\Url|\Nette\Http\UrlImmutable|string $url,
		int $cacheTtl = null,
		bool $randommizeUserAgent = true,
		array $headers = [],
	): string {
		$urlString = (string)$url;

		if ($cacheTtl !== null) {
			$cacheKey = md5(self::class . $urlString);
			$cacheResult = $this->cache->get($cacheKey);
			if ($cacheResult !== null) {
				return $cacheResult;
			}
		}

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
	 * @param array<string, mixed> $headers
	 * @return mixed Returns null if error occure, including HTTP errors 4xx
	 */
	public function getJson(
		\Nette\Http\Url|\Nette\Http\UrlImmutable|string $url,
		?int $cacheTtl = null,
		bool $randommizeUserAgent = true,
		array $headers = [],
	): mixed {
		$body = $this->get(
			url: $url,
			cacheTtl: $cacheTtl,
			randommizeUserAgent: $randommizeUserAgent,
			headers: $headers,
		);
		if ($body === '') {
			return null;
		}

		return Json::decode($body);
	}

	/**
	 * Load redirect URL. If next URL is also redirect URL, follow it too, until non-redirect URL is found or
	 * max redirect count is achieved (respects HTTP Client settings)
	 * @param array<string, mixed> $headers
	 */
	public function loadFinalRedirectUrl(
		\Nette\Http\Url|\Nette\Http\UrlImmutable|string $url,
		int $cacheTtl = null,
		bool $randommizeUserAgent = true,
		array $headers = [],
	): string {
		$urlString = (string)$url;
		if ($cacheTtl !== null) {
			$cacheKey = md5(self::class . __FUNCTION__ . $urlString);
			$cacheResult = $this->cache->get($cacheKey);
			if ($cacheResult !== null) {
				return $cacheResult;
			}
		}

		// @TODO Twitter URLs are not returning 'location' header if User-Agent is provided
		// if ($randommizeUserAgent) {
		// 	$headers['user-agent'] = UserAgents::getRandom();
		// }

		assert($this->httpClient instanceof \GuzzleHttp\Client);
		$request = new \GuzzleHttp\Psr7\Request('GET', $urlString, $headers);
		$response = $this->httpClient->sendRequest($request);
		$headersRedirect = $response->getHeader(\GuzzleHttp\RedirectMiddleware::HISTORY_HEADER);
		if ($headersRedirect === []) {
			throw new \Exception(sprintf('Unable to load redirect URL from "%s"', $url));
		}
		$finalUrl = end($headersRedirect);
		if ($cacheTtl !== null) {
			$this->cache->set($cacheKey, $finalUrl, $cacheTtl);
		}

		return $finalUrl;
	}
}
