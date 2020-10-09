<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Command;

abstract class Command extends \TelegramCustomWrapper\Events\Events
{
	protected function getChatId() {
		return $this->update->message->chat->id;
	}
	protected function getMessageId() {
		return $this->update->message->message_id;
	}
}
