<?php declare(strict_types=1);

namespace App\MiniCurl;

class Response
{
	private $raw;
	private $body;
	private $headers = [];
	private $info;
	/** @var false|\DateTimeImmutable */
	private $cacheHit;

	public function __construct(string $rawResponse, array $curlInfo, $cacheHit = false)
	{
		if ($cacheHit !== false && ($cacheHit instanceof \DateTimeImmutable === false)) { // @TODO use constructor union in PHP 8
			throw new \InvalidArgumentException(sprintf('Argument $cacheHit can be only false or DateTimeImmutable but "%s" provided.', gettype($cacheHit) === 'object' ? get_class($cacheHit) : gettype($cacheHit)));
		}
		$this->cacheHit = $cacheHit;
		$this->raw = $rawResponse;
		list($headerString, $body) = explode("\r\n\r\n", $rawResponse, 2);
		$this->body = $body;
		$this->processHeaderString($headerString);
		$this->info = $curlInfo;
	}

	public function processHeaderString(string $headerString): void
	{
		$headers = explode("\r\n", $headerString);
		array_shift($headers);
		foreach ($headers as $header) {
			list($headerName, $headerValue) = explode(': ', $header, 2);
			$this->headers[mb_strtolower($headerName)] = $headerValue;
		}
	}

	public function getRaw(): string
	{
		return $this->raw;
	}

	/** @return \DateTimeImmutable|false Datetime when cache was created or false if no cache was available or caching was disabled */
	public function cacheHit()
	{
		return $this->cacheHit;
	}

	public function getBody(): string
	{
		return $this->body;
	}

	/** @TODO cache decoded json */
	public function getBodyAsJson(bool $assoc = false, int $depth = 512, int $jsonOptions = JSON_THROW_ON_ERROR)
	{
		return json_decode($this->body, $assoc, $depth, $jsonOptions);
	}

	/**
	 * Get full curl_info() or one specific value (if not exists, return null)
	 *
	 * @param ?string $key Get value of specific key or set null to get all curl info as array
	 * @return array<string,mixed>|mixed|null
	 */
	public function getInfo(?string $key = null)
	{
		if (is_null($key)) {
			return $this->info;
		} else if (isset($this->info[$key])) {
			return $this->info[$key];
		} else {
			return null;
		}
	}

	public function getCode(): int
	{
		return $this->getInfo('http_code');
	}

	/**
	 * @return array<string,string>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function getHeader(string $key): ?string
	{
		$keyLower = mb_strtolower($key);
		return $this->headers[$keyLower] ?? null;
	}
}
