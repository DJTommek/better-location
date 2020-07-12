<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;

class HelpCommand extends Command
{
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		$text = sprintf('%s Welcome to private @%s!', Icons::LOCATION, TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Bot is currently in development. Check source code on Github <a href="%1$s%2$s">%2$s</a> for more info.', 'https://github.com/', 'DJTommek/better-location') . PHP_EOL;
		$this->reply($text);
	}
}