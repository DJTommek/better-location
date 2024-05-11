<?php declare(strict_types=1);

namespace App\Factory;

class GeocachingApiFactory
{
	public function __construct(
		private readonly string $cookie,
		private readonly int $cacheTtl,
	) {
	}

	public function create(): \App\Geocaching\Client
	{
		$client = new \App\Geocaching\Client($this->cookie);
		$client->setCache($this->cacheTtl);
		return $client;
	}
}
