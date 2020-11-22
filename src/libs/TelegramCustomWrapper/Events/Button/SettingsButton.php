<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class SettingsButton extends Button
{
	const CMD = SettingsCommand::CMD;

	public function handleWebhookUpdate()
	{
		$text = sprintf('<b>Settings</b>') . PHP_EOL;
		$text .= sprintf('Choose one of the settings via buttons below:') . PHP_EOL;

		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				[ // button
					'text' => 'Settings:',
					'callback_data' => self::CMD,
				],
			],
		];

		$this->replyButton($text, $replyMarkup);
	}
}
