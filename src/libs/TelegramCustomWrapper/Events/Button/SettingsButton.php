<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use unreal4u\TelegramAPI\Telegram\Types;

class SettingsButton extends Button
{
	const CMD = SettingsCommand::CMD;

	public function handleWebhookUpdate()
	{
		$text = sprintf('<b>Settings</b>') . PHP_EOL;
		$text .= sprintf('Choose one of the settings via buttons below:') . PHP_EOL;

		$replyMarkup = new Types\Inline\Keyboard\Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				new Types\Inline\Keyboard\Button([
					'text' => 'Settings:',
					'callback_data' => self::CMD,
				]),
			],
		];

		$this->replyButton($text, [
			'disable_web_page_preview' => true,
			'reply_markup' => $replyMarkup,
		]);
	}
}
