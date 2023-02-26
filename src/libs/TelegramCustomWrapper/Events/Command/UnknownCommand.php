<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Icons;

class UnknownCommand extends Command
{
	public function handleWebhookUpdate()
	{
		$text = sprintf('%s Sorry, I don\'t know this command...', Icons::ERROR) . PHP_EOL; // @TODO add info which command was written
		$text .= sprintf('Try %s to get list of all commands.', HelpCommand::getTgCmd(!$this->isTgPm()));
		$this->reply($text);
	}
}
