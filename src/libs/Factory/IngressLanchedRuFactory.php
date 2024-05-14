<?php declare(strict_types=1);

namespace App\Factory;

class IngressLanchedRuFactory
{
	public function __construct(
		private readonly int $cacheTtl,
	) {
	}

	public function create(): \App\IngressLanchedRu\Client
	{
		$client = new \App\IngressLanchedRu\Client();
		$client->setCache($this->cacheTtl);
		return $client;
	}
}
