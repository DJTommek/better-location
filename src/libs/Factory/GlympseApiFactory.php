<?php declare(strict_types=1);

namespace App\Factory;

use DJTommek\GlympseApi\GlympseApi;
use DJTommek\GlympseApi\Types\AccessToken;
use Nette\Utils\FileSystem;

class GlympseApiFactory
{
	public function __construct(
		private readonly string $apiKey,
		private readonly string $username,
		private readonly string $password,
		private readonly string $accessTokenPath,
	) {
	}

	public function create(): GlympseApi
	{
		$client = new GlympseApi($this->apiKey);
		$client->setUsername($this->username);
		$client->setPassword($this->password);

		$accessToken = $this->loadCachedValidAccessToken();
		if ($accessToken === null) {
			$accessToken = $client->accountLogin();
			$this->saveAccessToken($accessToken);
		}

		$client->setAccessToken($accessToken);
		return $client;
	}

	private function loadCachedValidAccessToken(): ?AccessToken
	{
		if (!file_exists($this->accessTokenPath)) {
			return null;
		}

		$accessTokenRaw = FileSystem::read($this->accessTokenPath);
		$unserialized = unserialize($accessTokenRaw, [
			'allowed_classes' => [AccessToken::class, \DateTimeImmutable::class, \DateInterval::class, \stdClass::class],
		]);

		assert($unserialized->expiration instanceof \DateTimeImmutable);
		if (time() > $unserialized->expiration->getTimestamp()) {
			return null;
		}

		return $unserialized->accessToken;
	}

	private function saveAccessToken(AccessToken $accessToken): void
	{
		$toSerialize = (object)[
			'accessToken' => $accessToken,
			'expiration' => (new \DateTimeImmutable())->add($accessToken->expiresIn),
		];
		FileSystem::write($this->accessTokenPath, serialize($toSerialize));
	}
}
