<?php declare(strict_types=1);

namespace App\Repository;

class UserRepository extends Repository
{
	public function fromTelegramId(int $telegramId): ?UserEntity
	{
		$sql = 'SELECT * FROM better_location_user WHERE user_telegram_id = ?';
		$row = $this->db->query($sql, $telegramId)->fetch();
		return $row ? UserEntity::fromRow($row) : null;
	}

	public function insert(int $telegramId, string $displayName): void
	{
		$this->db->query('INSERT INTO better_location_user 
    			(user_telegram_id, user_telegram_name, user_last_update, user_registered) 
    			VALUES 
                (?, ?, UTC_TIMESTAMP(), UTC_TIMESTAMP())',
			$telegramId, $displayName
		);
	}

	public function update(UserEntity $entity): void
	{
		$this->db->query('UPDATE better_location_user 
				SET user_telegram_name = ?, user_location_lat = ?, user_location_lon = ?, user_location_last_update = ?, user_last_update = UTC_TIMESTAMP() 
				WHERE user_id = ?',
			$entity->telegramName, $entity->getLat(), $entity->getLon(), $entity->lastLocationUpdate, $entity->id
		);
	}
}
