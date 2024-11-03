<?php declare(strict_types=1);

namespace App\IgnoreFilter;

use App\TelegramCustomWrapper\Events\Special\ChatMigrateFromEvent;
use Nette\Utils\Json;
use unreal4u\TelegramAPI\Telegram;

final class IgnoreFilterParams
{
	/**
	 * @var list<int>
	 */
	public array $ignoredTelegramSenderIds;

	public function addTelegramSender(int $userId): void
	{
		$ids = $this->ignoredTelegramSenderIds;
		$ids[] = $userId;
		$this->ignoredTelegramSenderIds = array_values(array_unique($ids));
	}

	public static function fromSQL(?string $paramsRaw): self
	{
		$result = new self();
		if ($paramsRaw !== null) {
			$params = Json::decode($paramsRaw);
			$result->ignoredTelegramSenderIds = $params->ignoredTelegramSenderIds ?? [];
		}
		return $result;
	}

	public function toSQL(): string
	{
		return Json::encode($this);
	}
}
