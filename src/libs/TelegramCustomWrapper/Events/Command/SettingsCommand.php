<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

class SettingsCommand extends Command
{
	const CMD = '/settings';

	public function handleWebhookUpdate()
	{
		$this->processSettings(false);
	}
}
