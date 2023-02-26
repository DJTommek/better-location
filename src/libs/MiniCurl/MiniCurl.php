<?php declare(strict_types=1);

namespace App\MiniCurl;

use App\Config;
use App\MiniCurl\Exceptions\ExecException;
use App\MiniCurl\Exceptions\InitException;
use App\MiniCurl\Exceptions\InvalidResponseException;
use App\MiniCurl\Exceptions\TimeoutException;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;

class MiniCurl
{
	private const CACHE_FOLDER = Config::FOLDER_TEMP . '/mini-curl/cached-responses';

	/**
	 * List of real useragents to make difficult to detect scraping
	 *
	 * @see https://techblog.willshouse.com/2012/01/03/most-common-user-agents/ (last updated 2020-12-02
	 * @see https://github.com/Kikobeats/top-user-agents
	 */
	private const USERAGENTS = [
		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:82.0) Gecko/20100101 Firefox/82.0',
		// @TODO if random http user agent is used, caching mechanism will cache response only for that specific useragent.
		// Solution: set random useragent after checking cache and only if is not already set by user
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
//		'Mozilla/5.0 (X11; Linux x86_64; rv:82.0) Gecko/20100101 Firefox/82.0',
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.66 Safari/537.36',
//		'Mozilla/5.0 (Windows NT 10.0; rv:78.0) Gecko/20100101 Firefox/78.0 ',
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:83.0) Gecko/20100101 Firefox/83.0 ',
//		'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:82.0) Gecko/20100101 Firefox/82.0',
//		'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.193 Safari/537.36',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Safari/605.1.15',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_6) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.1 Safari/605.1.15',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.183 Safari/537.36',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:82.0) Gecko/20100101 Firefox/82.0',
//		'Mozilla/5.0 (X11; Linux x86_64; rv:83.0) Gecko/20100101 Firefox/83.0 ',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36',
//		'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.111 Safari/537.36',
	];

	private const EXPECTED_ENCODING = 'UTF-8';

	private $cacheAllowed = false;
	private $cacheTtl = 0;
	private $url;
	private $curl;
	private $allowRandomUseragent = true;
	private $autoConvertEncoding = true;
	/** @var array<int,mixed> Options to CURL method (predefined, can be updated) */
	private $options = [
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HEADER => true,
		CURLOPT_NOBODY => false,
		CURLOPT_CONNECTTIMEOUT => 5,
		CURLOPT_TIMEOUT => 5,
		CURLOPT_FOLLOWLOCATION => true,
	];
	private $httpHeaders = [];
	private $httpCookies = [];

	public function __construct(string|Url|UrlImmutable $url)
	{
		$this->url = (string)$url;
		$this->curl = curl_init($this->url);
		if ($this->curl === false) {
			throw new InitException('CURL can\'t be initialited.');
		}
	}

	/**
	 * Set curl_option as in curl_setopt()
	 *
	 * @see self::setHttpCookie() to nicer way to set cookies
	 * @see self::setHttpHeader() to nicer way to set HTTP header
	 */
	public function setCurlOption(int $optionKey, $optionValue): self
	{
		if ($optionKey === CURLOPT_HTTPHEADER && count($this->httpHeaders)) {
			throw new \InvalidArgumentException('Updating CURLOPT_HTTPHEADER is not possible because setHttpHeader() method was already used.');
		} else if ($optionKey === CURLOPT_COOKIE && count($this->httpCookies)) {
			throw new \InvalidArgumentException('Updating CURLOPT_COOKIE is not possible because setHttpCookie() method was already used.');
		}
		$this->options[$optionKey] = $optionValue;
		return $this;
	}

	/** @param array $curlOpts indexed array of options to curl_setopt() */
	public function setCurlOptions(array $curlOpts): self
	{
		foreach ($curlOpts as $curlOptionKey => $curlOptionValue) {
			$this->setCurlOption($curlOptionKey, $curlOptionValue);
		}
		return $this;
	}

	public function setHttpHeader(string $name, string $value): self
	{
		if (isset($this->options[CURLOPT_HTTPHEADER])) {
			throw new \RuntimeException('setHttpHeader() cant be used since CURLOPT_HTTPHEADER is already defined.');
		}
		$this->httpHeaders[$name] = $value;
		return $this;
	}

	public function setHttpCookie(string $name, string $value): self
	{
		if (isset($this->options[CURLOPT_COOKIE])) {
			throw new \RuntimeException('setHttpCookie() cant be used since CURLOPT_COOKIE is already defined.');
		}
		$this->httpCookies[$name] = $value;
		return $this;
	}

	/**
	 * Allow response caching.
	 * If caching is enabled, just before request is created unique identifier of request (URL, all CURL options including POST parameters).
	 * If there already exists cache with this ID, instead of making request, this cached response is returned.
	 * If cache is missing, it will perform real request and save it as this ID for future usage.
	 *
	 * @param int $ttl How old in seconds from now cache can be to load. Even if cache is found but it's too old, it is not returned.<br>
	 * Set 0 to completely disable caching (saving and loading) for this request.
	 */
	public function allowCache(int $ttl): self
	{
		if ($ttl === 0) {
			$this->cacheAllowed = false;
		} else if ($ttl < 0) {
			throw new \InvalidArgumentException('Parameter $interval must be 0 or higher but "%d" provided.');
		} else {
			$this->cacheAllowed = true;
			if (is_dir(self::CACHE_FOLDER) === false && @mkdir(self::CACHE_FOLDER, 0755, true) === false) {
				throw new \Exception(sprintf('Error while creating folder for MiniCurl cached responses: "%s"', error_get_last()['message']));
			}
		}
		$this->cacheTtl = $ttl;
		return $this;
	}

	public function allowRandomUseragent(bool $allow = true): self
	{
		$this->allowRandomUseragent = $allow;
		return $this;
	}

	public function allowAutoConvertEncoding(bool $allow): self
	{
		$this->autoConvertEncoding = $allow;
		return $this;
	}

	/**
	 * Perform request (or load cached response) and return Response
	 *
	 * @param int|null $requireResponseCode Throw exception if response code is different
	 * @return Response
	 * @throws ExecException
	 * @throws InvalidResponseException
	 */
	public function run(?int $requireResponseCode = 200): Response
	{
		if ($this->allowRandomUseragent) {
			$randomUseragent = self::USERAGENTS[array_rand(self::USERAGENTS)];
			$this->setHttpHeader('user-agent', $randomUseragent);
		}

		if (empty($this->options[CURLOPT_COOKIE]) && count($this->httpCookies)) {
			$cookie = '';
			foreach ($this->httpCookies as $cookieName => $cookieValue) {
				$cookie .= sprintf('%s=%s; ', $cookieName, $cookieValue);
			}
			$this->options[CURLOPT_COOKIE] = $cookie;
		}

		if (empty($this->options[CURLOPT_HTTPHEADER]) && count($this->httpHeaders)) {
			$this->options[CURLOPT_HTTPHEADER] = [];
			foreach ($this->httpHeaders as $headerName => $headerValue) {
				$this->options[CURLOPT_HTTPHEADER][] = sprintf('%s: %s', $headerName, $headerValue);
			}
		}

		if ($this->cacheAllowed) {
			$cacheId = $this->generateCacheId();
			if ($cachedResponse = $this->loadFromCache($cacheId)) {
				Debugger::log(sprintf('Cache ID "%s" hit!', $cacheId), Debugger::DEBUG);
				return $cachedResponse;
			}
			Debugger::log(sprintf('Cache ID "%s" miss.', $cacheId), Debugger::DEBUG);
		}

		curl_setopt_array($this->curl, $this->options);
		$curlResponse = curl_exec($this->curl);
		if ($curlResponse === false) {
			$curlErrno = curl_errno($this->curl);
			$exceptionText = sprintf('CURL request error %s: "%s"', $curlErrno, curl_error($this->curl));
			$curlErrno === CURLE_OPERATION_TIMEOUTED
				? throw new TimeoutException($exceptionText)
				: throw new ExecException($exceptionText);
		}

		if ($this->autoConvertEncoding) {
			$detectedEncoding = mb_detect_encoding($curlResponse, [self::EXPECTED_ENCODING, 'ISO-8859-1', 'windows-1252'], true);
			if ($detectedEncoding !== self::EXPECTED_ENCODING) {
				$curlResponse = mb_convert_encoding($curlResponse, self::EXPECTED_ENCODING, $detectedEncoding);
			}
		}

		$curlInfo = curl_getinfo($this->curl);
		$response = new Response($curlResponse, $curlInfo);
		if (is_null($requireResponseCode) === false && $response->getCode() !== $requireResponseCode) {
			throw new InvalidResponseException(sprintf('Invalid response code "%d" but required "%d" for URL "%s".', $response->getCode(), $requireResponseCode, $this->url), $response->getCode());
		}
		if (isset($cacheId)) {
			$this->saveToCache($cacheId, $curlResponse, $curlInfo);
		}
		return $response;
	}

	private function generateCacheId(): string
	{
		return Cache::generateId($this->url, $this->options);
	}

	private function getCachePath(string $cacheId)
	{
		return sprintf('%s/%s.json', self::CACHE_FOLDER, $cacheId);
	}

	private function loadFromCache(string $cacheId): ?Response
	{
		$path = $this->getCachePath($cacheId);
		if (is_file($path)) {
			$cache = Cache::fromString(file_get_contents($path));
			if ($cache->datetime->getTimestamp() - time() + $this->cacheTtl > 0) { // check if cache is not expired
				return new Response(
					$cache->rawResponse,
					$cache->curlInfo,
					$cache->datetime
				);
			}
		}
		return null;
	}

	private function saveToCache(string $cacheId, string $rawResponse, array $curlInfo): Cache
	{
		$path = $this->getCachePath($cacheId);
		$cache = new Cache($cacheId, $rawResponse, $curlInfo, time());
		if (@file_put_contents($path, $cache->__toString()) === false) {
			throw new \Exception(sprintf('Error while saving cache to file: "%s"', error_get_last()['message']));
		}
		return $cache;
	}

	/** @return array<string,string>|string|null */
	public static function loadHeaders(string $url, ?string $key = null)
	{
		$client = new self($url);
		$client->allowRandomUseragent(false);
		$client->setCurlOption(CURLOPT_FOLLOWLOCATION, false);
		// $client->setCurlOption(CURLOPT_NOBODY, true); // @HACK temporary disabled, see https://github.com/DJTommek/better-location/issues/74
		$client->setCurlOption(CURLOPT_FRESH_CONNECT, true);
		$response = $client->run(null);
		return $response->getHeaders($key);
	}

	public static function loadRedirectUrl(string $url): ?string
	{
		return self::loadHeaders($url, 'location');
	}
}
