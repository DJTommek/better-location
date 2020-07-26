<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;

class UnknownCommand extends Command
{
	/**
	 * UnknownCommand constructor.
	 *
	 * @param $update
	 * @param $tgLog
	 * @param $loop
	 * @throws \Exception
	 */
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		$text = sprintf('%s Sorry, I don\'t know this command...', Icons::ERROR) . PHP_EOL; // @TODO add info which command was written
		$text .= sprintf('Try /help to get list of all commands.');
		$this->reply($text);
	}
}