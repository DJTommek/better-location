<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

class HelpCommand extends Command
{
	const CMD = '/help';

	public function handleWebhookUpdate()
	{
		$this->processHelp();
	}
}
