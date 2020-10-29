<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;

class SettingsCommand extends Command
{
	const CMD = '/settings';

	public function __construct($update)
	{
		parent::__construct($update);

		$text = sprintf('%s <b>Settings</b> for @%s', Icons::COMMAND, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Settings is currently not available. Go back to %s.', HelpCommand::getCmd(!$this->isPm())) . PHP_EOL;
		$this->reply($text, ['disable_web_page_preview' => true]);
	}
}
