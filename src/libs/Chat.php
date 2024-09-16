<?php declare(strict_types=1);

namespace App;

use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;

class Chat
{
	private ?BetterLocationMessageSettings $messageSettings = null;
	private ChatEntity $chatEntity;

	public function __construct(
		private readonly ChatRepository $chatRepository,
		int $telegramChatId,
		string $telegramChatType,
		string $telegramChatName,
	) {
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

	public function settingsTryLoadIngressPortal(?bool $enable = null): bool
	{
		if ($enable !== null) {
			$this->chatEntity->settingsTryLoadIngressPortal = $enable;
			$this->update();
		}
		return $this->chatEntity->settingsTryLoadIngressPortal;
	}

	public function setLastUpdate(\DateTimeInterface $lastUpdate): void
	{
		$this->chatEntity->lastUpdate = \DateTimeImmutable::createFromInterface($lastUpdate);
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
		$this->chatEntity = $this->chatRepository->getById($this->chatEntity->id);
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
			$this->messageSettings->tryLoadIngressPortal($this->settingsTryLoadIngressPortal());
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

	public function tgMigrateTo(int $newTgChatId): void
	{
		$oldTelegramChatId = $this->chatEntity->telegramId;
		$this->chatEntity->telegramId = $newTgChatId;
		$this->update();

		Debugger::log(sprintf(
			'Telegram chat ID %d was migrated from TG ID %d to TG ID %d.',
			$this->chatEntity->id,
			$oldTelegramChatId,
			$newTgChatId,
		), Debugger::INFO);
	}
}
