<?php declare(strict_types=1);

namespace App\Repository;

class ChatRepository extends Repository
{
	public function fromTelegramId(int $telegramId): ?ChatEntity
	{
		$sql = 'SELECT * FROM better_location_chat WHERE chat_telegram_id = ?';
		$row = $this->db->query($sql, $telegramId)->fetch();
		return $row ? ChatEntity::fromRow($row) : null;
	}

	public function insert(int $telegramId, string $telegramChatType, string $displayName): void
	{
		$this->db->query('INSERT INTO better_location_chat 
    			(chat_telegram_id, chat_telegram_type, chat_telegram_name, chat_last_update, chat_registered) 
    			VALUES 
                (?, ?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
			$telegramId, $telegramChatType, $displayName
		);
	}

	public function update(ChatEntity $entity): void
	{
		$this->db->query('UPDATE better_location_chat SET 
                                chat_telegram_name = ?, 
                                chat_settings_preview = ?, 
                                chat_settings_output_type = ?, 
                                chat_settings_show_address = ?,
                                chat_settings_try_load_ingress_portal = ? ,
                                chat_plugin_url = ?, 
                                chat_last_update = ? 
				WHERE chat_id = ?',
			$entity->telegramName,
			$entity->settingsPreview ? 1 : 0,
			$entity->getSettingsOutputType(),
			$entity->settingsShowAddress ? 1 : 0,
			$entity->settingsTryLoadIngressPortal ? 1 : 0,
			$entity->pluginUrl?->getAbsoluteUrl(),
			$entity->lastUpdate->setTimezone(new \DateTimeZone('UTC'))->format(self::DATETIME_FORMAT),
			$entity->id
		);
	}
}
