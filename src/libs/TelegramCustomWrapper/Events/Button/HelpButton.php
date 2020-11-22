<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\TelegramCustomWrapper\Events\Command\HelpCommand;

class HelpButton extends Button
{
	const CMD = HelpCommand::CMD;

	public function handleWebhookUpdate()
	{
		$this->processHelp(true);
	}
}
