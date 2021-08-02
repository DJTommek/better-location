<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\Coordinates;

class FavouritesRepository extends Repository
{
	/** @return array<FavouritesEntity> */
	public function byUserId(int $userId): array
	{
		$sql = 'SELECT * FROM better_location_favourites WHERE user_id = ?';
		$rows = $this->db->query($sql, $userId)->fetchAll();
		return FavouritesEntity::fromRows($rows);
	}

	public function add(int $userId, Coordinates $coordinates, string $title): void
	{
		$sql = 'INSERT INTO better_location.better_location_favourites (user_id, status, lat, lon, title) VALUES (?, ?, ?, ?, ?)';
		$sql .= ' ON DUPLICATE KEY UPDATE status = ?, title = ?';
		$this->db->query($sql,
			$userId, self::ENABLED, $coordinates->getLat(), $coordinates->getLon(), $title,
			self::ENABLED, $title
		);
	}

	public function remove(int $id): void
	{
		$this->db->query('UPDATE better_location_favourites SET status = ? WHERE id = ?', self::DELETED, $id);
	}

	public function rename(int $id, string $title): void
	{
		$this->db->query('UPDATE better_location_favourites SET title = ? WHERE id = ?', htmlspecialchars($title), $id);
	}
}
