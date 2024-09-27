<?php declare(strict_types=1);

namespace App\Repository;

class StaticMapCacheRepository extends Repository
{
	public function findById(string $id): ?StaticMapCacheEntity
	{
		$row = $this->db->query('SELECT * FROM better_location_static_map_cache WHERE id = ?', $id)->fetch();
		return $row ? StaticMapCacheEntity::fromRow($row) : null;
	}

	/**
	 * @see Warning: Nette\Http\Url cant be used, see https://github.com/nette/http/issues/178
	 */
	public function save(string $id, string $url): void
	{
		$sql = 'INSERT INTO better_location_static_map_cache (id, url) VALUES (?, ?) ON DUPLICATE KEY UPDATE url=url';
		$this->db->query($sql, $id, $url);
	}
}
