<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\Chat;
use App\Config;
use App\Icons;
use App\Pluginer\Pluginer;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Button\SettingsButton;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

trait SettingsTrait
{
	abstract function getMessageSettings(): BetterLocationMessageSettings;

	abstract function getPluginer(): ?Pluginer;

	abstract function getChat(): ?Chat;

	abstract function isTgPm(): ?bool;

	abstract function getTgChatId(): int;

	protected function processSettings(): array
	{
		$processedCollection = new ProcessedMessageResult(
			$this->processExample->getExampleCollection(),
			$this->getMessageSettings(),
			$this->getPluginer(),
		);
		$processedCollection->process();

		$text = sprintf('%s <b>Chat settings</b> for @%s. ', Icons::SETTINGS, Config::TELEGRAM_BOT_NAME);
		if ($this->isTgPm()) {
			$text .= PHP_EOL . sprintf('%s This private chat settings will be used while sending messages via inline mode, overriding chat settings.', Icons::INFO) . PHP_EOL . PHP_EOL;
		}
		$text .= 'Example message:' . PHP_EOL;
		$text .= $processedCollection->getText();
		$replyMarkup = $processedCollection->getMarkup(1);

		$previewButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->getChat()->settingsPreview()) {
			$previewButton->text = sprintf('%s Map preview', Icons::ENABLED);
			$previewButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_PREVIEW);
		} else {
			$previewButton->text = sprintf('%s Map preview', Icons::DISABLED);
			$previewButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_PREVIEW);
		}
		$buttonRow[] = $previewButton;

		$showAddressButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->getChat()->settingsShowAddress()) {
			$showAddressButton->text = sprintf('%s Address', Icons::ENABLED);
			$showAddressButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SHOW_ADDRESS);
		} else {
			$showAddressButton->text = sprintf('%s Address', Icons::DISABLED);
			$showAddressButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SHOW_ADDRESS);
		}
		$buttonRow[] = $showAddressButton;

		$sendNativeLocationButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->getChat()->getSendNativeLocation()) {
			$sendNativeLocationButton->text = sprintf('%s Native location', Icons::ENABLED);
			$sendNativeLocationButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SEND_NATIVE_LOCATION);
		} else {
			$sendNativeLocationButton->text = sprintf('%s Native location', Icons::DISABLED);
			$sendNativeLocationButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SEND_NATIVE_LOCATION);
		}
		$buttonRow[] = $sendNativeLocationButton;

		$replyMarkup->inline_keyboard[] = $buttonRow;
		$chatSettingsUrl = Config::getAppUrl('/chat/' . $this->getTgChatId());
		$replyMarkup->inline_keyboard[] = [
			TelegramHelper::loginUrlButton('More settings', $chatSettingsUrl),
		];

		return [
			$text,
			$replyMarkup,
			[
				'disable_web_page_preview' => !$this->getChat()->settingsPreview(),
			],
		];
	}
}
