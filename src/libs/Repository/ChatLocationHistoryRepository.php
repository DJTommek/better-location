<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\CoordinatesInterface;

class ChatLocationHistoryRepository extends Repository
{
	public function insert(int $telegramUpdateId, int $chatId, int $userId, \DateTimeInterface $dateTime, CoordinatesInterface $coords, string $input): void
	{
		$this->db->query('INSERT INTO better_location_chat_location_history 
    			(telegram_update_id, chat_id, user_id, timestamp, latitude, longitude, input) 
    			VALUES 
                (?, ?, ?, ?, ?, ?, ?)',
			$telegramUpdateId, $chatId, $userId, $dateTime->getTimestamp(), $coords->getLat(), $coords->getLon(), $input
		);
	}
}
