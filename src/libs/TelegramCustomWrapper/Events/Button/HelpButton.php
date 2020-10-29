<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\TelegramCustomWrapper\Events\Command\HelpCommand;

class HelpButton extends Button
{
	const CMD = HelpCommand::CMD;

	public function __construct($update)
	{
		parent::__construct($update);
		$this->processHelp(true);
	}
}
