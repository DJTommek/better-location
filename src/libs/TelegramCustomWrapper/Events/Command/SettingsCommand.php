<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\BetterLocation\ProcessExample;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\SettingsTrait;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class SettingsCommand extends Command
{
	use SettingsTrait;

	const CMD = '/settings';
	const ICON = Icons::SETTINGS;
	const DESCRIPTION = 'Adjust your settings';

	public function __construct(
		private readonly ProcessExample $processExample,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->isAdmin()) {
			[$text, $markup, $options] = $this->processSettings();
			$this->reply($text, $markup, $options);
		} else {
			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard = [
				[ // row of buttons
					new Button([
						'text' => sprintf('%s Open in PM', Icons::SETTINGS),
						'url' => TelegramHelper::generateStart(StartCommand::SETTINGS),
					]),
				],
			];
			$this->reply(sprintf('%s Command <code>%s</code> is available only in private message (open @%s) or to chat admins.',
				Icons::ERROR, self::getTgCmd(), Config::TELEGRAM_BOT_NAME
			), $replyMarkup);
		}
	}
}
