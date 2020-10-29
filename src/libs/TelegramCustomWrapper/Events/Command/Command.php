<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

abstract class Command extends \App\TelegramCustomWrapper\Events\Events
{
	protected function getChatId()
	{
		return $this->update->message->chat->id;
	}

	protected function getMessageId()
	{
		return $this->update->message->message_id;
	}
}
