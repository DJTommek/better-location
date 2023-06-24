<?php declare(strict_types=1);

namespace App\Http;

use Nette\Utils\Json;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class Response implements ResponseInterface
{
	public function __construct(
		private readonly int $status = 200,
		/**
		 * @var array<string, array<string>>
		 */
		private readonly array $headers = [],
		private readonly ?string $body = null,
		private readonly string $version = '1.1',
		private readonly ?string $reason = null,
	) {
	}

	public function body(): ?string
	{
		return $this->body;
	}

	public function __toString(): string
	{
		return $this->body();
	}

	public function json(bool $forceArray = false): mixed
	{
		return Json::decode($this->body(), $forceArray);
	}

	public function getBody()
	{
		throw new NotSupportedException('Use body() instead');
	}

	/**
	 * @return array<string, array<string>>
	 */
	public function getHeaders(): array
	{
		return $this->headers;
	}

	public function hasHeader(string $name): bool
	{
		return isset($this->headers[mb_strtolower($name)]);
	}

	/**
	 * @return array<string>
	 */
	public function getHeader(string $name): array
	{
		return $this->headers[mb_strtolower($name)] ?? [];
	}

	public function getHeaderLine(string $name): string
	{
		return implode(',', $this->getHeader($name));
	}

	public function getProtocolVersion(): string
	{
		return $this->version;
	}

	public function getStatusCode(): int
	{
		return $this->status;
	}

	public function getReasonPhrase(): ?string
	{
		return $this->reason;
	}

	public function withProtocolVersion(string $version)
	{
		throw new NotSupportedException();
	}

	public function withHeader(string $name, $value)
	{
		throw new NotSupportedException();
	}

	public function withAddedHeader(string $name, $value)
	{
		throw new NotSupportedException();
	}

	public function withoutHeader(string $name)
	{
		throw new NotSupportedException();
	}

	public function withBody(StreamInterface $body)
	{
		throw new NotSupportedException();
	}

	public function withStatus(int $code, string $reasonPhrase = '')
	{
		throw new NotSupportedException();
	}
}
