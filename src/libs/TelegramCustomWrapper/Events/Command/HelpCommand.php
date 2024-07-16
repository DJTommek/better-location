<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\BetterLocation\ProcessExample;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\HelpTrait;

class HelpCommand extends Command
{
	use HelpTrait;

	const CMD = '/help';
	const ICON = Icons::INFO;
	const DESCRIPTION = 'Learn more about me, ' . Config::TELEGRAM_BOT_NAME;

	public function __construct(
		private readonly ProcessExample $processExample,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		[$text, $markup, $options] = $this->processHelp();
		$this->reply($text, $markup, $options);
	}
}
