<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

abstract class Special extends \App\TelegramCustomWrapper\Events\Events
{
	public function getTgMessage(): Telegram\Types\Message
	{
		return TelegramHelper::getMessage($this->update);
	}
}
