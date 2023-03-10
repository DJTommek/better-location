<?php

namespace App\Pluginer;

use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

class Validator
{
	private const SCHEMA_PATH = __DIR__ . '/request.schema.json';

	private ?\stdClass $schema = null;
	private \JsonSchema\Validator $validator;
	private bool $executed = false;

	public function __construct()
	{
		$this->validator = new \JsonSchema\Validator();
	}

	public function validate(\stdClass $data): void
	{
		if ($this->schema === null) {
			$this->schema = Json::decode(file_get_contents(self::SCHEMA_PATH));
		}

		$this->validator->validate($data, $this->schema);
		$this->executed = true;
	}

	public function isValid(): bool
	{
		$this->assertExecuted();
		return $this->validator->isValid();
	}

	/**
	 * @return string[]
	 */
	public function getErrors(): array
	{
		$this->assertExecuted();
		$errors = $this->validator->getErrors();
		return array_map([self::class, 'mapJsonErrors'], $errors);
	}

	private function assertExecuted(): void
	{
		if ($this->executed === false) {
			throw new \RuntimeException('You must execute validate() first.');
		}
	}

	private static function mapJsonErrors(array $error): string
	{
		return sprintf('[%s] %s', $error['property'], $error['message']);
	}
}
