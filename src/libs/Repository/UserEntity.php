<?php declare(strict_types=1);

namespace App\Repository;

use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;

class UserEntity extends Entity
{
	/**
	 * @readonly
	 */
	public int $id;
	/**
	 * @readonly
	 */
	public int $telegramId;
	public string $telegramName;
	public \DateTimeImmutable $registered;
	public \DateTimeImmutable $lastUpdate;
	public ?\DateTimeImmutable $lastLocationUpdate = null;
	public ?CoordinatesImmutable $lastLocation = null;

	public static function fromRow(array|\PDORow $row): self
	{
		$entity = new self();
		$entity->id = $row['user_id'];
		$entity->telegramId = $row['user_telegram_id'];
		$entity->telegramName = $row['user_telegram_name'];
		$entity->registered = new \DateTimeImmutable($row['user_registered']);
		$entity->lastUpdate = new \DateTimeImmutable($row['user_last_update']);
		if ($row['user_location_lat'] !== null && $row['user_location_lon'] !== null && $row['user_location_last_update'] !== null) {
			$entity->lastLocation = new CoordinatesImmutable($row['user_location_lat'], $row['user_location_lon']);
			$entity->lastLocationUpdate = new \DateTimeImmutable($row['user_location_last_update']);
		}
		return $entity;
	}

	public function setLastLocation(CoordinatesInterface $coords, \DateTimeInterface $datetime = null): void
	{
		if ($coords instanceof CoordinatesImmutable === false) {
			$coords = new CoordinatesImmutable($coords->getLat(), $coords->getLon());
		}
		$this->lastLocation = $coords;

		if ($datetime === null) {
			$datetime = new \DateTimeImmutable();
		} else if ($datetime instanceof \DateTimeImmutable === false) {
			$datetime = \DateTimeImmutable::createFromInterface($datetime);
		}
		$this->lastLocationUpdate = $datetime;
	}
}
