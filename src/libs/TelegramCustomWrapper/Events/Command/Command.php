<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use unreal4u\TelegramAPI\Telegram;

abstract class Command extends \App\TelegramCustomWrapper\Events\Events
{
	public function getMessage(): Telegram\Types\Message
	{
		return $this->update->message;
	}
}
