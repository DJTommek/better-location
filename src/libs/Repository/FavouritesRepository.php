<?php declare(strict_types=1);

namespace App\Repository;

class FavouritesRepository extends Repository
{
	private const MAX_LENGTH = 30;

	/**
	 * @param int $userId
	 * @param int[] $statuses
	 * @return FavouritesEntity[]
	 */
	public function findByUser(int $userId, array $statuses = [self::ENABLED]): array
	{
		if ($statuses === []) {
			throw new \InvalidArgumentException('At least one status is required');
		}
		$sql = 'SELECT * FROM better_location_favourites WHERE user_id = ? AND status IN (' . self::inHelper($statuses) . ')';
		$params = array_merge([$userId], $statuses);
		$rows = $this->db->query($sql, ...$params)->fetchAll();
		return FavouritesEntity::fromRows($rows);
	}

	public function findByIdAndUser(int $id, int $userId): ?FavouritesEntity
	{
		$row = $this->db->query('SELECT * FROM better_location_favourites WHERE id = ? AND user_id = ?', $id, $userId)->fetch();
		if ($row === false) {
			return null;
		}
		return FavouritesEntity::fromRow($row);
	}

	public function add(int $userId, float $lat, float $lon, string $title): void
	{
		$sql = 'INSERT INTO better_location_favourites (user_id, status, lat, lon, title) VALUES (?, ?, ?, ?, ?)';
		$sql .= ' ON DUPLICATE KEY UPDATE status = ?';
		$this->db->query($sql,
			$userId, self::ENABLED, $lat, $lon, $title,
			self::ENABLED
		);
	}

	public function remove(int $id): void
	{
		$this->db->query('UPDATE better_location_favourites SET status = ? WHERE id = ?', self::DELETED, $id);
	}

	public function removeByUserLatLon(int $userId, float $lat, float $lon): void
	{
		$this->db->query('UPDATE better_location_favourites SET status = ? WHERE user_id = ? AND lat = ? AND lon = ?',
			self::DELETED, $userId, $lat, $lon
		);
	}

	public function rename(int $id, string $title): void
	{
		$title = trim($title);
		if ($title === '') {
			throw new \DomainException('Favorite title must not be empty.');
		}

		if (mb_strlen($title) > self::MAX_LENGTH) {
			throw new \DomainException(sprintf('Favorite title is too long, maximum is %d characters.', self::MAX_LENGTH));
		}

		$this->db->query('UPDATE better_location_favourites SET title = ? WHERE id = ?', $title, $id);
	}
}
