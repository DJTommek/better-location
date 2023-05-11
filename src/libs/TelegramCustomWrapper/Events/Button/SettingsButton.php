<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\TelegramCustomWrapper\Events\SettingsTrait;
use App\Utils\Strict;

class SettingsButton extends Button
{
	use SettingsTrait;

	const CMD = SettingsCommand::CMD;

	const ACTION_SETTINGS_PREVIEW = 'preview';
	const ACTION_SETTINGS_SEND_NATIVE_LOCATION = 'send_native_location';
	const ACTION_SETTINGS_SHOW_ADDRESS = 'show_address';

	public function handleWebhookUpdate(): void
	{
		if ($this->isAdmin()) {
			if (count($this->params) > 1) {
				switch ($this->params[0]) {
					case self::ACTION_SETTINGS_PREVIEW:
						$previewEnabled = Strict::boolval($this->params[1]);
						$this->chat->settingsPreview($previewEnabled);
						$this->flash(sprintf('%s Map preview for locations was %s.', Icons::SUCCESS, $previewEnabled ? 'enabled' : 'disabled'));
						break;
					case self::ACTION_SETTINGS_SHOW_ADDRESS:
						$showAddress = Strict::boolval($this->params[1]);
						$this->chat->settingsShowAddress($showAddress);
						$this->flash(sprintf('%s Showing address locations was %s.', Icons::SUCCESS, $showAddress ? 'enabled' : 'disabled'));
						break;
					case self::ACTION_SETTINGS_SEND_NATIVE_LOCATION:
						$sendNativeLocation = Strict::boolval($this->params[1]);
						$this->chat->setSendNativeLocation($sendNativeLocation);
						$this->flash(sprintf('%s Sending native Telegram location was %s.', Icons::SUCCESS, $sendNativeLocation ? 'enabled' : 'disabled'));
						break;
				}
			}

			[$text, $markup, $options] = $this->processSettings();
			$this->replyButton($text, $markup, $options);
		} else {
			$this->flash(sprintf('%s You are not admin of this chat.', Icons::ERROR), true);
		}
	}
}
