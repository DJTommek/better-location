<?php declare(strict_types=1);

namespace App\Factory;

class GuzzleClientFactory
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

	/**
	 * @param array<string,mixed> $config
	 */
	public function create(array $config = []): \GuzzleHttp\Client
	{
		$config = array_merge($config, $this->config);

		return new \GuzzleHttp\Client($config);
	}
}
