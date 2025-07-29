<?php declare(strict_types=1);

namespace App;

use App\IgnoreFilter\IgnoreFilter;
use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\Repository\ChatUserRelation;
use App\Repository\ChatUserRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

class Chat
{
	/** Lazy loading, use getMessageSettings() instead. */
	private BetterLocationMessageSettings $messageSettings;
	/** Lazy loading, use getIgnoreFilter() instead. */
	private IgnoreFilter $ignoreFilter;

	public function __construct(
		private readonly ChatRepository $chatRepository,
		private readonly ChatUserRepository $chatUserRepository,
		private ChatEntity $chatEntity,
	) {
	}

	public function getIgnoreFilter(): IgnoreFilter
	{
		if (isset($this->ignoreFilter) === false) {
			$ignoredSenderIds = $this->chatUserRepository->findUserIds(ChatUserRelation::IGNORE_SENDER, $this->chatEntity->id);
			$this->ignoreFilter = new IgnoreFilter($ignoredSenderIds);
		}
		return $this->ignoreFilter;
	}

	public function addSenderToIgnoreFilter(int $userId): void
	{
		$this->chatUserRepository->add(ChatUserRelation::IGNORE_SENDER, $this->getEntity()->id, $userId);
		unset($this->ignoreFilter);
	}

	public function removeSenderFromIgnoreFilter(int $userId): void
	{
		$this->chatUserRepository->delete(ChatUserRelation::IGNORE_SENDER, $this->getEntity()->id, $userId);
		unset($this->ignoreFilter);
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
		unset($this->ignoreFilter);
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

	public function getChatSettingsUrl(): UrlImmutable
	{
		return Config::getAppUrl('/chat/' . $this->chatEntity->telegramId);
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if (isset($this->messageSettings) === false) {
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
		),
			Debugger::INFO);
	}
}
