<?php declare(strict_types=1);

namespace App\Factory;

class FoursquareApiFactory
{
	public function __construct(
		private readonly string $clientId,
		private readonly string $clientSecret,
		private readonly int $cacheTtl,
	) {
	}

	public function create(): \App\Foursquare\Client
	{
		$client = new \App\Foursquare\Client($this->clientId, $this->clientSecret);
		$client->setCache($this->cacheTtl);
		return $client;
	}
}
