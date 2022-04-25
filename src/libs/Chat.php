<?php declare(strict_types=1);

namespace App;

use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;

class Chat
{
	/** @var BetterLocationMessageSettings */
	private $messageSettings;
	/** @var ChatEntity */
	private $chatEntity;
	/** @var ChatRepository */
	private $chatRepository;

	public function __construct(int $telegramChatId, string $telegramChatType, string $telegramChatName)
	{
		$db = Factory::Database();
		$this->chatRepository = new ChatRepository($db);
		if (($this->chatEntity = $this->chatRepository->fromTelegramId($telegramChatId)) === null) {
			$this->chatRepository->insert($telegramChatId, $telegramChatType, $telegramChatName);
			$this->chatEntity = $this->chatRepository->fromTelegramId($telegramChatId);
		}
	}

	public function settingsPreview(?bool $enable = null): bool
	{
		if ($enable !== null) {
			$this->chatEntity->settingsPreview = $enable;
			$this->update();
		}
		return $this->chatEntity->settingsPreview;
	}

	public function touchLastUpdate(): void
	{
		$this->update();
	}

	public function settingsOutputType(?int $value = null): int
	{
		if ($value !== null) {
			$this->chatEntity->settingsOutputType = $value;
			$this->update();
		}
		return $this->chatEntity->settingsOutputType;
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

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->messageSettings === null) {
			$this->messageSettings = BetterLocationMessageSettings::loadByChatId($this->chatEntity->id);
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

	public function getEntity(): ?ChatEntity
	{
		return $this->chatEntity;
	}

}
