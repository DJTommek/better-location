<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;

class UnknownCommand extends Command
{
	/**
	 * UnknownCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		$text = sprintf('%s Sorry, I don\'t know this command...', Icons::ERROR) . PHP_EOL; // @TODO add info which command was written
		$text .= sprintf('Try %s@%s to get list of all commands.', FavouritesCommand::CMD, \Config::TELEGRAM_BOT_NAME);
		$this->reply($text);
	}
}
