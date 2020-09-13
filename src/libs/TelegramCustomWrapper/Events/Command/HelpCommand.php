<?php

namespace TelegramCustomWrapper\Events\Command;

class HelpCommand extends Command
{
	/**
	 * HelpCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);
		$this->processHelp();
	}
}
