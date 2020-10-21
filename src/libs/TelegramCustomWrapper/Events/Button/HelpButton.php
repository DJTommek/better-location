<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Button;

use TelegramCustomWrapper\Events\Command\HelpCommand;

class HelpButton extends Button
{
	const CMD = HelpCommand::CMD;

	/**
	 * HelpButton constructor.
	 *
	 * @param $update
	 * @throws \BetterLocation\Service\Exceptions\InvalidLocationException
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		$this->processHelp(true);
	}
}
