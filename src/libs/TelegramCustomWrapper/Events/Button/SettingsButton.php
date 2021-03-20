<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\Utils\Strict;

class SettingsButton extends Button
{
	const CMD = SettingsCommand::CMD;

	const ACTION_SETTINGS_PREVIEW = 'preview';

	public function handleWebhookUpdate()
	{
		if (count($this->params) > 1) {
			switch ($this->params[0]) {
				case self::ACTION_SETTINGS_PREVIEW:
					$previewEnabled = $this->user->settings()->setPreview(Strict::boolval($this->params[1]));
					$this->flash(sprintf('%s Map preview for locations was %s.', Icons::SUCCESS, $previewEnabled ? 'enabled' : 'disabled'));
					break;
			}
		}
		$this->processSettings(true);
	}
}
