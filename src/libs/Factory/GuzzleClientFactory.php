<?php declare(strict_types=1);

namespace App\Factory;

use App\Config;
use App\Http\Guzzle\Middlewares\AlwaysRedirectMiddleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;

class GuzzleClientFactory
{
	/**
	 * @var array<RequestOptions::*, mixed>
	 */
	private array $config = [
		RequestOptions::CONNECT_TIMEOUT => Config::GUZZLE_OPTION_DEFAULT_TIMEOUT,
		RequestOptions::READ_TIMEOUT => Config::GUZZLE_OPTION_DEFAULT_TIMEOUT,
		RequestOptions::TIMEOUT => Config::GUZZLE_OPTION_DEFAULT_TIMEOUT,
		RequestOptions::PROXY => Config::GUZZLE_OPTION_DEFAULT_PROXY,
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
