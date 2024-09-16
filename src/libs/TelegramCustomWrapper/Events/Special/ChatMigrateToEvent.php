<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use unreal4u\TelegramAPI\Telegram;

class ChatMigrateToEvent extends Special
{
	public function handleWebhookUpdate(): void
	{
		assert($this->chat !== null);

		$targetTelegramChatId = $this->update->message->migrate_to_chat_id;
		assert($targetTelegramChatId !== 0);

		$this->chat->tgMigrateTo($targetTelegramChatId);
	}
}
