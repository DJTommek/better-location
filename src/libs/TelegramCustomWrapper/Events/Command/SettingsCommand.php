<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;

class SettingsCommand extends Command
{
	/**
	 * SettingsCommand constructor.
	 *
	 * @param $update
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		$text = sprintf('%s <b>Settings</b> for @%s', Icons::COMMAND, TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Settings is currently not available. Go back to /help.') . PHP_EOL;
		$this->reply($text, ['disable_web_page_preview' => true]);
	}
}