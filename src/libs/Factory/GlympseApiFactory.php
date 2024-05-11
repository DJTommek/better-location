<?php declare(strict_types=1);

namespace App\Factory;

use DJTommek\GlympseApi\GlympseApi;

class GlympseApiFactory
{
	public function __construct(
		private readonly string $apiKey,
		private readonly string $username,
		private readonly string $password,
	) {
	}

	public function create(): GlympseApi
	{
		$client = new GlympseApi($this->apiKey);
		$client->setUsername($this->username);
		$client->setPassword($this->password);
		$client->accountLogin();
		$accessToken = $client->accountLogin();
		$client->setAccessToken($accessToken);
		return $client;
	}
}
