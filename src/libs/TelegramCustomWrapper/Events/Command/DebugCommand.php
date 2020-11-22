<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;

class DebugCommand extends Command
{
	const CMD = '/debug';

	public function handleWebhookUpdate()
	{
		$text = sprintf('%s <b>Debug</b> for @%s.', Icons::COMMAND, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('This chat ID <code>%s</code>!', $this->getChatId()) . PHP_EOL;
		$text .= sprintf('Your user ID <code>%s</code>!', $this->getFromId()) . PHP_EOL;
		$this->reply($text);
	}
}
