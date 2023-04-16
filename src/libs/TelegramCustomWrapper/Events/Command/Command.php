<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use unreal4u\TelegramAPI\Telegram;

abstract class Command extends \App\TelegramCustomWrapper\Events\Events
{
	public static function getTgCmd(bool $withSuffix = false): string
	{
		if (defined('static::CMD') === false) {
			throw new \RuntimeException(sprintf('%s is missing class constant CMD', static::class));
		}

		if ($withSuffix) {
			return sprintf('%s@%s', static::CMD, Config::TELEGRAM_BOT_NAME);
		} else {
			return static::CMD;
		}
	}

	public function getTgMessage(): Telegram\Types\Message
	{
		return $this->update->message;
	}
}
