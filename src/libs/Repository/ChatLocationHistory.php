<?php declare(strict_types=1);

namespace App\Repository;

use App\BetterLocation\Service\AbstractService;
use DJTommek\Coordinates\CoordinatesInterface;

class ChatLocationHistory extends Repository
{
	/**
	 * @param class-string<AbstractService> $sourceService
	 */
	public function insert(
		int $telegramUpdateId,
		int $chatId,
		int $userId,
		\DateTimeInterface $dateTime,
		CoordinatesInterface $coords,
		string $input,
		string $sourceService,
		?string $sourceType,
	): void {
		$this->db->query('INSERT INTO better_location_chat_location_history 
    			(telegram_update_id, chat_id, user_id, timestamp, latitude, longitude, input, source_service_id, source_type) 
    			VALUES 
                (?, ?, ?, ?, ?, ?, ?, ?, ?)',
			$telegramUpdateId,
			$chatId,
			$userId,
			$dateTime->getTimestamp(),
			$coords->getLat(),
			$coords->getLon(),
			$input,
			$sourceService::getId(),
			$sourceType,
		);
	}
}
