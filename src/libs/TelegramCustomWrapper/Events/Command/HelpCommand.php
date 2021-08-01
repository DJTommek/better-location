<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;

class HelpCommand extends Command
{
	const CMD = '/help';
	const ICON = Icons::INFO;
	const DESCRIPTION = 'Learn more about me, ' . Config::TELEGRAM_BOT_NAME;

	public function handleWebhookUpdate()
	{
		$this->processHelp();
	}
}
