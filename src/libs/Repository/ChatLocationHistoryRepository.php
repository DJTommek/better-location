<?php declare(strict_types=1);

namespace App\Repository;

use DJTommek\Coordinates\CoordinatesInterface;

class ChatLocationHistoryRepository extends Repository
{
	/**
	 * @return list<ChatLocationHistoryEntity>
	 */
	public function loadByTelegramChatId(int $telegramChatId): array
	{
		$sql = 'SELECT clh.id, clh.telegram_update_id, clh.timestamp, clh.latitude, clh.longitude, clh.input, clh.address, user.*, chat.*
		FROM better_location_chat_location_history clh 
		LEFT JOIN better_location_user user ON user.user_id = clh.user_id
		LEFT JOIN better_location_chat chat ON chat.chat_id = clh.chat_id
		WHERE chat.chat_telegram_id = ? 
		ORDER BY timestamp DESC 
		LIMIT 1000';
		$rows = $this->db->query($sql, $telegramChatId)->fetchAll();
		return ChatLocationHistoryEntity::fromRows($rows);
	}

	public function insert(
		int $telegramUpdateId,
		int $chatId,
		int $userId,
		\DateTimeInterface $dateTime,
		CoordinatesInterface $coords,
		string $input,
		?string $address,
	): void {
		$this->db->query('INSERT INTO better_location_chat_location_history 
    			(telegram_update_id, chat_id, user_id, timestamp, latitude, longitude, input, address) 
    			VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?)',
			$telegramUpdateId,
			$chatId,
			$userId,
			$dateTime->getTimestamp(),
			$coords->getLat(),
			$coords->getLon(),
			$input,
			$address,
		);
	}
}
