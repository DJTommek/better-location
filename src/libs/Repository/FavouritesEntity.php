<?php declare(strict_types=1);

namespace App\Repository;

use DJTommek\Coordinates\CoordinatesInterface;

class FavouritesEntity extends Entity implements CoordinatesInterface
{
	/** @var int */
	public $id;
	/** @var int */
	public $userId;
	/** @var int */
	public $status;
	/** @var float */
	public $lat;
	/** @var float */
	public $lon;
	/** @var string */
	public $title;

	#[\Override]
 public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['id'];
		$entity->userId = $row['user_id'];
		$entity->status = $row['status'];
		$entity->lat = $row['lat'];
		$entity->lon = $row['lon'];
		$entity->title = $row['title'];
		return $entity;
	}

	#[\Override]
 public function getLat(): float
	{
		return $this->lat;
	}

	#[\Override]
 public function getLon(): float
	{
		return $this->lon;
	}

	#[\Override]
 public function getLatLon(string $delimiter = ','): string
	{
		return sprintf('%F,%F', $this->lat, $this->lon);
	}
}
