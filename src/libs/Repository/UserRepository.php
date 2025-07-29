<?php declare(strict_types=1);

namespace App\Repository;

class UserRepository extends Repository
{
	public function findById(int $id): ?UserEntity
	{
		$sql = 'SELECT * FROM better_location_user WHERE user_id = ?';
		$row = $this->db->query($sql, $id)->fetch();
		return $row ? UserEntity::fromRow($row) : null;
	}

	public function findByTelegramId(int $telegramId): ?UserEntity
	{
		$sql = 'SELECT * FROM better_location_user WHERE user_telegram_id = ?';
		$row = $this->db->query($sql, $telegramId)->fetch();
		return $row ? UserEntity::fromRow($row) : null;
	}

	/**
	 * @param list<int> $telegramIds
	 * @return array<int, string> Telegram ID as key, Telegram displayname as value
	 */
	public function findTelegramNamesByTelegramIds(array $telegramIds): array
	{
		if ($telegramIds === []) {
			return [];
		}
		$sql = 'SELECT user_telegram_id, user_telegram_name FROM better_location_user WHERE user_telegram_id IN (' . self::inHelper($telegramIds) . ') ORDER BY user_telegram_name';
		$query = $this->db->query($sql, ...$telegramIds);
		$result = [];
		while($row = $query->fetch()) {
			$result[$row['user_telegram_id']] = $row['user_telegram_name'];
		}
		return $result;
	}

	public function insert(int $telegramId, string $displayName): void
	{
		$this->db->query('INSERT INTO better_location_user 
    			(user_telegram_id, user_telegram_name, user_last_update, user_registered) 
    			VALUES 
                (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
			$telegramId,
			$displayName,
		);
	}

	public function update(UserEntity $entity): void
	{
		$timezone = new \DateTimeZone('UTC');

		$this->db->query('UPDATE better_location_user 
				SET user_telegram_name = ?, user_location_lat = ?, user_location_lon = ?, user_location_last_update = ?, user_last_update = ? 
				WHERE user_id = ?',
			$entity->telegramName,
			$entity->lastLocation?->lat,
			$entity->lastLocation?->lon,
			$entity->lastLocationUpdate?->setTimezone($timezone)->format(self::DATETIME_FORMAT),
			$entity->lastUpdate->setTimezone($timezone)->format(self::DATETIME_FORMAT),
			$entity->id,
		);
	}
}
