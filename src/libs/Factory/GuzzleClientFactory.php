<?php declare(strict_types=1);

namespace App\Factory;

use App\Http\Guzzle\Middlewares\AlwaysRedirectMiddleware;
use GuzzleHttp\HandlerStack;

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
		if (isset($config['handler']) === false) {
			$handlerStack = HandlerStack::create();
			$handlerStack->after('allow_redirects', new AlwaysRedirectMiddleware(), 'always_allow_redirects');
			$config['handler'] = $handlerStack;
		}

		$config = array_merge($config, $this->config);

		return new \GuzzleHttp\Client($config);
	}
}
