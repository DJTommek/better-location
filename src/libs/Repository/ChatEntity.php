<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\Strict;

class ChatEntity extends Entity
{
	const OUTPUT_TYPE_MESSAGE = 0;
	const OUTPUT_TYPE_LOCATION = 1;
	const OUTPUT_TYPE_FILE_GPX = 2;
	const OUTPUT_TYPE_FILE_KML = 3;

	const CHAT_TYPE_PRIVATE = 'private';
	const CHAT_TYPE_GROUP = 'group';
	const CHAT_TYPE_SUPERGROUP = 'supergroup';
	const CHAT_TYPE_CHANNEL = 'channel';

	/**
	 * @var int
	 * @readonly
	 */
	public $id;
	/**
	 * @var int
	 * @readonly
	 */
	public $telegramId;
	/** @var string */
	public $telegramName;
	/** @var string */
	public $telegramChatType;
	/** @var \DateTimeImmutable */
	public $registered;
	/** @var \DateTimeImmutable */
	public $lastUpdate;
	/** @var bool */
	public $settingsPreview;
	/** @var int */
	public $settingsOutputType;

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
		$entity->settingsOutputType = $row['chat_settings_output_type'];
		return $entity;
	}
}
