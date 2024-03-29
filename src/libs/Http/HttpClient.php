<?php declare(strict_types=1);

namespace App\Http;

use App\Config;
use App\Factory;
use App\Utils\Strict;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use GuzzleHttp\Psr7\Request;
use Nette\Caching\Cache;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Psr\Http\Message\UriInterface;

/**
 * Disclaimer 2023-06-18: This handler is still in active development:
 * - More features are planned to be implemented from MiniCurl, such as: partial download, download only HTTP headers,
 *      random useragents, automatic encoding conversion, POST requests
 * - Signature of classes, attributes and methods might be completely changed
 */
class HttpClient
{
	private const DEFAULT_TIMEOUT = 5;
	/**
	 * @var array<string,mixed>
	 */
	private array $config = [
		'connect_timeout' => self::DEFAULT_TIMEOUT,
		'read_timeout' => self::DEFAULT_TIMEOUT,
		'timeout' => self::DEFAULT_TIMEOUT,
	];
	private ?GuzzleClient $client = null;
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
	 * @param int|false|null $ttl TTL in seconds. Set 0 to disable caching.
	 */
	public function allowCache(int|false|null $ttl): self
	{
		$this->cacheTtl = (int)$ttl;
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
	 * @param string|UriInterface|Url|UrlImmutable $uri Absolute URL should be used for request
	 * @param array<string, mixed> $options Request options to apply.
	 *
	 * @throws HttpException
	 */
	public function get(string|UriInterface|Url|UrlImmutable $uri, array $options = []): Response
	{
		$url = Strict::urlImmutable($uri);

		$request = new Request(
			method: 'GET',
			uri: (string)$url,
			headers: $this->httpHeaders,
		);

		if ($this->httpCookies !== []) {
			$cookieJar = CookieJar::fromArray($this->httpCookies, $url->getDomain());
			$options['cookies'] = $cookieJar;
		}

		return $this->send($request, $options);
	}

	/**
	 * Send an HTTP request.
	 *
	 * @param array<string, mixed> $options Request options to apply.
	 */
	public function send(Request $request, array $options = []): Response
	{
		if ($this->canUseCache()) {
			$cacheKey = $this->getCacheKey($request, $options);
			$cache = $this->getCacheStorage();

			$cachedResponse = $cache->load($cacheKey);
			if ($cachedResponse !== null) {
				return $cachedResponse;
			}
		}

		$responseOriginal = $this->getClient()->send($request, $options);
		$responseUtils = new Response(
			status: $responseOriginal->getStatusCode(),
			headers: $responseOriginal->getHeaders(),
			body: (string)$responseOriginal->getBody(),
			version: $responseOriginal->getProtocolVersion(),
			reason: $responseOriginal->getReasonPhrase(),
		);

		if ($this->canUseCache()) {
			$cache->save($cacheKey, $responseUtils);
		}

		return $responseUtils;
	}

	private function canUseCache(): bool
	{
		return $this->cacheTtl > 0;
	}

	private function getClient(): GuzzleClient
	{
		if ($this->client === null) {
			$this->client = new GuzzleClient($this->config);
		}

		return $this->client;
	}

	public function setClient(GuzzleClient $client): self
	{
		$this->client = $client;

		return $this;
	}

	private function getCacheStorage(): Cache
	{
		if ($this->cacheStorage === null) {
			$cacheStorage = Factory::cache(Config::CACHE_NAMESPACE_HTTP_CLIENT); // Default cache storage
			$this->setCacheStorage($cacheStorage);
		}
		return $this->cacheStorage;
	}

	public function setCacheStorage(Cache $cacheStorage): void
	{
		$this->cacheStorage = $cacheStorage;
	}

	/**
	 * @param Request $request
	 * @param array<string, mixed> $options
	 * @return string
	 */
	private function getCacheKey(Request $request, array $options): string
	{
		// User-Agent in HTTP headers is sometimes randomized, ignore it from cache key
		$clonedRequest = $request->withoutHeader('user-agent');

		$keyRaw = serialize($clonedRequest);
		$keyRaw .= serialize($options);
		$keyRaw .= serialize($this->httpCookies);
		return md5($keyRaw);
	}
}
