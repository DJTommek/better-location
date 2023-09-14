<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use unreal4u\TelegramAPI\Telegram;

/**
 * Bot member status in some chat has been changed.
 */
class MyChatMemberEvent extends Special
{
	public function getTgFrom(): Telegram\Types\User
	{
		return $this->update->my_chat_member->from;
	}

	public function getTgChat(): Telegram\Types\Chat
	{
		return $this->update->my_chat_member->chat;
	}

	public function hasTgMessage(): bool
	{
		return false;
	}

	public function handleWebhookUpdate(): void
	{
		// Do nothing
	}
}


