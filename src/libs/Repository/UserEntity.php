<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\Coordinates;

class UserEntity extends Entity
{
	/**
	 * @var int
	 * @readonly
	 */
	public $id;
	/**
	 * @var int
	 * @readonly
	 */
	public $telegramId;
	/** @var string */
	public $telegramName;
	/** @var \DateTimeImmutable */
	public $registered;
	/** @var \DateTimeImmutable */
	public $lastUpdate;
	/** @var ?\DateTimeImmutable */
	public $lastLocationUpdate;
	/** @var ?Coordinates */
	public $lastLocation;

	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['user_id'];
		$entity->telegramId = $row['user_telegram_id'];
		$entity->telegramName = $row['user_telegram_name'];
		$entity->registered = new \DateTimeImmutable($row['user_registered']);
		$entity->lastUpdate = new \DateTimeImmutable($row['user_last_update']);
		if ($row['user_location_lat'] !== null && $row['user_location_lon'] !== null && $row['user_location_last_update'] !== null) {
			$entity->lastLocation = new Coordinates($row['user_location_lat'], $row['user_location_lon']);
			$entity->lastLocationUpdate = new \DateTimeImmutable($row['user_location_last_update']);
		}
		return $entity;
	}

	public function getLat(): ?float
	{
		return $this->lastLocation ? $this->lastLocation->getLat() : null;
	}

	public function getLon(): ?float
	{
		return $this->lastLocation ? $this->lastLocation->getLon() : null;
	}

	public function setLastLocation(Coordinates $coords): void
	{
		$this->lastLocation = $coords;
		$this->lastLocationUpdate = new \DateTimeImmutable();
	}
}
