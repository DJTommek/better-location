<?php

namespace TelegramCustomWrapper\Events\Special;

abstract class Special extends \TelegramCustomWrapper\Events\Events
{
	protected function getChatId() {
		return $this->update->message->chat->id;
	}
	protected function getMessageId() {
		return $this->update->message->message_id;
	}
}