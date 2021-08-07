<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\Utils\Strict;

class SettingsButton extends Button
{
	const CMD = SettingsCommand::CMD;

	const ACTION_SETTINGS_PREVIEW = 'preview';

	const ACTION_SETTINGS_SEND_NATIVE_LOCATION = 'send_native_location';

	public function handleWebhookUpdate()
	{
		if ($this->isAdmin()) {
			if (count($this->params) > 1) {
				switch ($this->params[0]) {
					case self::ACTION_SETTINGS_PREVIEW:
						$previewEnabled = Strict::boolval($this->params[1]);
						$this->user->setSettingsPreview($previewEnabled);
						$this->flash(sprintf('%s Map preview for locations was %s.', Icons::SUCCESS, $previewEnabled ? 'enabled' : 'disabled'));
						break;
					case self::ACTION_SETTINGS_SEND_NATIVE_LOCATION:
						$sendNativeLocation = Strict::boolval($this->params[1]);
						$this->user->setSettingsSendNativeLocation($sendNativeLocation);
						$this->flash(sprintf('%s Sending native Telegram location was %s.', Icons::SUCCESS, $sendNativeLocation ? 'enabled' : 'disabled'));
						break;
				}
			}
			$this->processSettings(true);
		} else {
			$this->flash(sprintf('%s You are not admin of this chat.', Icons::ERROR), true);
		}
	}
}
