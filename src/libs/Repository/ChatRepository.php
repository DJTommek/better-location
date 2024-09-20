<?php declare(strict_types=1);

namespace App\Repository;

class ChatRepository extends Repository
{
	public function getById(int $chatId): ChatEntity
	{
		$sql = 'SELECT * FROM better_location_chat WHERE chat_id = ?';
		$row = $this->db->query($sql, $chatId)->fetch();
		return ChatEntity::fromRow($row);
	}

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
			$telegramId,
			$telegramChatType,
			$displayName,
		);
	}

	/**
	 * Find all chats, that given user ID can manage
	 *
	 * @return list<ChatEntity>
	 */
	public function findByAdminId(int $userId): array
	{
		$rows = $this->db->query('SELECT c.* FROM better_location_chat c
LEFT JOIN better_location_chat_members cm ON c.chat_id = cm.chat_member_chat_id
WHERE cm.chat_member_user_id = ?
ORDER BY chat_registered DESC',
			$userId)->fetchAll();
		return ChatEntity::fromRows($rows);
	}

	public function update(ChatEntity $entity): void
	{
		$this->db->query('UPDATE better_location_chat SET 
                                chat_status = ?, 
                                chat_telegram_name = ?, 
                                chat_telegram_id = ?, 
                                chat_settings_preview = ?, 
                                chat_settings_output_type = ?, 
                                chat_settings_show_address = ?,
                                chat_settings_try_load_ingress_portal = ? ,
                                chat_plugin_url = ?, 
                                chat_last_update = ? 
				WHERE chat_id = ?',
			$entity->status,
			$entity->telegramName,
			$entity->telegramId,
			$entity->settingsPreview ? 1 : 0,
			$entity->getSettingsOutputType(),
			$entity->settingsShowAddress ? 1 : 0,
			$entity->settingsTryLoadIngressPortal ? 1 : 0,
			$entity->pluginUrl?->getAbsoluteUrl(),
			$entity->lastUpdate->setTimezone(new \DateTimeZone('UTC'))->format(self::DATETIME_FORMAT),
			$entity->id,
		);
	}

	/**
	 * @return \Generator<ChatEntity>
	 */
	public function getAll(): \Generator
	{
		$query = $this->db->query('SELECT * FROM better_location_chat WHERE chat_status = ? ORDER BY chat_id DESC', Repository::ENABLED);
		while ($row = $query->fetch(\PDO::FETCH_LAZY)) {
			yield ChatEntity::fromRow($row);
		}
	}
}
