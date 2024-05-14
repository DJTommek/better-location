<?php declare(strict_types=1);

namespace App\Factory;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;

class HttpClientFactory
{
	public function __construct(
		private readonly Client $guzzleClient,
	) {
	}

	public function create(): ClientInterface
	{
		return $this->guzzleClient;
	}
}
