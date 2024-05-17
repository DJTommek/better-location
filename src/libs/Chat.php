<?php declare(strict_types=1);

namespace App;

use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use Nette\Http\UrlImmutable;

class Chat
{
	private ?BetterLocationMessageSettings $messageSettings = null;
	private ChatEntity $chatEntity;

	public function __construct(
		private readonly ChatRepository $chatRepository,
		int $telegramChatId,
		string $telegramChatType,
		string $telegramChatName,
	)
	{
		$chatEntity = $this->chatRepository->fromTelegramId($telegramChatId);
		if ($chatEntity === null) {
			$this->chatRepository->insert($telegramChatId, $telegramChatType, $telegramChatName);
			$chatEntity = $this->chatRepository->fromTelegramId($telegramChatId);
		}
		assert($chatEntity instanceof ChatEntity);
		$this->chatEntity = $chatEntity;
	}

	public function settingsPreview(?bool $enable = null): bool
	{
		if ($enable !== null) {
			$this->chatEntity->settingsPreview = $enable;
			$this->update();
		}
		return $this->chatEntity->settingsPreview;
	}

	public function settingsShowAddress(?bool $enable = null): bool
	{
		if ($enable !== null) {
			$this->chatEntity->settingsShowAddress = $enable;
			$this->update();
		}
		return $this->chatEntity->settingsShowAddress;
	}

	public function touchLastUpdate(): void
	{
		$this->update();
	}

	public function settingsOutputType(?int $value = null): int
	{
		if ($value !== null) {
			$this->chatEntity->setSettingsOutputType($value);
			$this->update();
		}
		return $this->chatEntity->getSettingsOutputType();
	}

	private function update(): void
	{
		$this->chatRepository->update($this->chatEntity);
		$this->chatEntity = $this->chatRepository->fromTelegramId($this->chatEntity->telegramId);
	}

	public function getTelegramChatName(): ?string
	{
		return $this->chatEntity->telegramName;
	}

	public function getPluginerUrl(): ?UrlImmutable
	{
		return $this->chatEntity->pluginUrl;
	}

	public function setPluginerUrl(?UrlImmutable $url): void
	{
		$this->chatEntity->pluginUrl = $url;
		$this->update();
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->messageSettings === null) {
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($this->chatEntity->id);
			$this->messageSettings->showAddress($this->settingsShowAddress());
		}
		return $this->messageSettings;
	}

	/* Backward compatibility methods originally from \App\UserSettings */

	/** @deprecated Use settingsOutputType() */
	public function setSendNativeLocation(bool $value): bool
	{
		$this->settingsOutputType($value ? ChatEntity::OUTPUT_TYPE_LOCATION : ChatEntity::OUTPUT_TYPE_MESSAGE);
		return $this->getSendNativeLocation();
	}

	/** @deprecated Use settingsOutputType() */
	public function getSendNativeLocation(): bool
	{
		return $this->settingsOutputType() === ChatEntity::OUTPUT_TYPE_LOCATION;
	}

	public function getEntity(): ChatEntity
	{
		return $this->chatEntity;
	}

}
