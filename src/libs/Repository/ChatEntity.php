<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\Strict;
use Nette\Http\UrlImmutable;

class ChatEntity extends Entity
{
	const OUTPUT_TYPE_MESSAGE = 0;
	const OUTPUT_TYPE_LOCATION = 1;
	const OUTPUT_TYPE_FILE_GPX = 2;
	const OUTPUT_TYPE_FILE_KML = 3;
	const OUTPUT_TYPE_SNEAK_BUTTONS = 4;

	public const OUTPUT_TYPES = [
		self::OUTPUT_TYPE_MESSAGE,
		self::OUTPUT_TYPE_LOCATION,
		self::OUTPUT_TYPE_FILE_GPX,
		self::OUTPUT_TYPE_FILE_KML,
		self::OUTPUT_TYPE_SNEAK_BUTTONS,
	];

	const CHAT_TYPE_PRIVATE = 'private';
	const CHAT_TYPE_GROUP = 'group';
	const CHAT_TYPE_SUPERGROUP = 'supergroup';
	const CHAT_TYPE_CHANNEL = 'channel';

	/** @readonly */
	public int $id;
	/** @readonly */
	public int $telegramId;
	public string $telegramName;
	public string $telegramChatType;
	public \DateTimeImmutable $registered;
	public \DateTimeImmutable $lastUpdate;
	public bool $settingsPreview;
	private int $settingsOutputType;
	public bool $settingsShowAddress;
	public ?UrlImmutable $pluginUrl;

	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['chat_id'];
		$entity->telegramId = $row['chat_telegram_id'];
		$entity->telegramName = $row['chat_telegram_name'];
		$entity->telegramChatType = $row['chat_telegram_type'];
		$entity->registered = new \DateTimeImmutable($row['chat_registered']);
		$entity->lastUpdate = new \DateTimeImmutable($row['chat_last_update']);
		$entity->settingsPreview = Strict::boolval($row['chat_settings_preview']);
		$entity->setSettingsOutputType($row['chat_settings_output_type']);
		$entity->settingsShowAddress = Strict::boolval($row['chat_settings_show_address']);
		$entity->pluginUrl = $row['chat_plugin_url'] === null ? null : new UrlImmutable($row['chat_plugin_url']);
		return $entity;
	}

	public function getSettingsOutputType(): int
	{
		return $this->settingsOutputType;
	}

	public function setSettingsOutputType(int $newValue): void
	{
		if (in_array($newValue, self::OUTPUT_TYPES, true) === false) {
			throw new \InvalidArgumentException('Invalid output type key');
		}
		if (
			$newValue === self::OUTPUT_TYPE_SNEAK_BUTTONS
			&& $this->telegramChatType !== self::CHAT_TYPE_CHANNEL
		) {
			throw new \InvalidArgumentException('Sneak buttons can be used only for channels');
		}

		$this->settingsOutputType = $newValue;
	}
}
