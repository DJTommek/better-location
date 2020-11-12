<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use unreal4u\TelegramAPI\Telegram;

abstract class Special extends \App\TelegramCustomWrapper\Events\Events
{
	public function getMessage(): Telegram\Types\Message
	{
		return $this->update->message;
	}
}
