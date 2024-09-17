<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;

class DebugCommand extends Command
{
	const CMD = '/debug';
	const ICON = Icons::SETTINGS;
	const DESCRIPTION = 'Basic technical information';

	public function handleWebhookUpdate(): void
	{
		$text = sprintf('%s <b>Debug</b> for @%s.', Icons::COMMAND, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('This chat ID <code>%s</code>!', $this->getTgChatId()) . PHP_EOL;
		if ($this->isTgTopicMessage()) {
			$text .= sprintf('This topic ID: <code>%s</code>', $this->getTgTopicId()) . PHP_EOL;
		}
		$text .= sprintf('Your user ID <code>%s</code>!', $this->getTgFromId()) . PHP_EOL;
		$this->reply($text);
	}
}
