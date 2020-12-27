<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\LoginUrl;

class SettingsCommand extends Command
{
	const CMD = '/settings';

	public function handleWebhookUpdate()
	{
		$text = sprintf('%s <b>Settings</b> for @%s', Icons::COMMAND, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Settings is currently not available. Go back to %s.', HelpCommand::getCmd(!$this->isPm())) . PHP_EOL;

		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[
				new Button([
					'text' => 'Settings in browser',
					'login_url' => new LoginUrl([
						'url' => 'https://tomas.palider.cz/projects/better-location/test/',
					]),
				]),
			],
		];

		$this->reply($text, $replyMarkup);
	}
}
