<?php

namespace App\Http;

use App\Config;
use App\Factory;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Nette\Caching\Cache;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Psr\Http\Message\UriInterface;

class HttpClient
{
	/**
	 * @var array<string,mixed>
	 */
	private array $config = [
		'connect_timeout' => 5,
		'read_timeout' => 5,
		'timeout' => 5,
	];
	private ?\GuzzleHttp\Client $client = null;
	private int $cacheTtl = 0;

	private ?Cache $cacheStorage = null;

	/**
	 * @var array<string,string>
	 */
	private array $httpCookies = [];

	/**
	 * @var array<string,string>
	 */
	private array $httpHeaders = [];

	/**
	 * @param array<string,mixed> $config
	 */
	public function __construct(array $config = [])
	{
		$this->config = array_merge($config, $this->config);
	}

	/**
	 * Allow caching.
	 *
	 * @param int $ttl TTL in seconds. Set 0 to disable caching.
	 */
	public function allowCache(int $ttl): self
	{
		$this->cacheTtl = $ttl;
		return $this;
	}

	public function setHttpCookie(string $name, string $value): self
	{
		$this->httpCookies[$name] = $value;
		return $this;
	}

	public function setHttpHeader(string $name, string $value): self
	{
		$this->httpHeaders[mb_strtolower($name)] = $value;
		return $this;
	}

	/**
	 * Create and send an HTTP GET request.
	 *
	 * Use an absolute path to override the base path of the client, or a
	 * relative path to append to the base path of the client. The URL can
	 * contain the query string as well.
	 *
	 * @param string|UriInterface|Url|UrlImmutable $uri URI object or string.
	 * @param array<string,mixed> $options Request options to apply.
	 *
	 * @throws GuzzleException
	 */
	public function get(string|UriInterface|Url|UrlImmutable $uri, array $options = []): Response
	{
		$this->createClient();

		if ($uri instanceof Url || $uri instanceof UrlImmutable) {
			$uri = (string)$uri;
		}

		$request = new Request(
			method: 'GET',
			uri: $uri,
			headers: $this->httpHeaders,
		);

		// @TODO set cookies
		// @TODO set http headers

		if ($this->cacheTtl > 0) {
			$cacheKey = $this->getCacheKey($request);
			$cache = $this->getCacheStorage();

			$cachedResponse = $cache->load($cacheKey);
			if ($cachedResponse !== null) {
				return $cachedResponse;
			}
		}

		$responseOriginal = $this->client->send($request, $options);
		$responseUtils = new Response(
			status: $responseOriginal->getStatusCode(),
			headers: $responseOriginal->getHeaders(),
			body: (string)$responseOriginal->getBody(),
			version: $responseOriginal->getProtocolVersion(),
			reason: $responseOriginal->getReasonPhrase(),
		);

		if (isset($cacheKey) && isset($cache)) {
			$cache->save($cacheKey, $responseUtils);
		}

		return $responseUtils;

	}

	private function createClient(): void
	{
		if ($this->client === null) {
			$this->client = new \GuzzleHttp\Client($this->config);
		}
	}

	private function getCacheStorage(): Cache
	{
		if ($this->cacheStorage === null) {
			$cacheStorage = Factory::cache(Config::CACHE_NAMESPACE_HTTP_CLIENT); // Default cache storage
			$this->setCacheStorage($cacheStorage);
		}
		return $this->cacheStorage;
	}

	private function setCacheStorage(Cache $cacheStorage): void
	{
		$this->cacheStorage = $cacheStorage;
	}

	private function getCacheKey(Request $request): string
	{
		$keyRaw = serialize($request);
		$keyRaw .= serialize($this->httpHeaders);
		$keyRaw .= serialize($this->httpCookies);
		return md5($keyRaw);
	}
}
