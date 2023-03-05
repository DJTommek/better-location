<?php

namespace App\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;

class HttpClientFactory
{
	private array $config = [
		'connect_timeout' => 5,
		'read_timeout' => 5,
		'timeout' => 5,
	];
	private ?Client $client = null;

	/**
	 * @var array<string,string>
	 */
	private array $cookies = [];

	public function __construct($config = [])
	{
		$this->config = array_merge($config, $this->config);
	}

	public function allowCache(): self
	{
		return $this;
	}

	public function setHttpCookie(string $name, string $value): self
	{
		$this->cookies[$name] = $value;
		return $this;
	}

	public function setHttpHeader(string $name, string $value): self
	{
		$this->cookies[mb_strtolower($name)] = $value;
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
	 * @param array $options Request options to apply.
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	public function get(string|UriInterface|Url|UrlImmutable $uri, array $options = []): ResponseInterface
	{
		$this->createClient();

		if ($uri instanceof Url || $uri instanceof UrlImmutable) {
			$uri = (string)$uri;
		}

		// @TODO set cookies
		// @TODO set http headers

		$this->client->get($uri, $options);
	}

	private function createClient(): void
	{
		if ($this->client === null) {
			$this->client = new Client($this->config);
		}
	}
}
