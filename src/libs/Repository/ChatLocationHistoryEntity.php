<?php declare(strict_types=1);

namespace App\Repository;

use App\Utils\DateImmutableUtils;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;

class ChatLocationHistoryEntity extends Entity implements CoordinatesInterface
{
	public readonly int $id;
	public readonly int $telegramUpdateId;
	public readonly \DateTimeImmutable $timestamp;
	public readonly float $latitude;
	public readonly float $longitude;
	public readonly string $input;

	public readonly ChatEntity $chat;
	public readonly UserEntity $user;

	public readonly CoordinatesImmutable $coordinates;

	/**
	 * @param array<string, mixed> $row
	 */
	public static function fromRow(array $row): self
	{
		$entity = new self();
		$entity->id = $row['id'];
		$entity->telegramUpdateId = $row['telegram_update_id'];
		$entity->chat = ChatEntity::fromRow($row);
		$entity->user = UserEntity::fromRow($row);
		$entity->timestamp = DateImmutableUtils::fromTimestamp($row['timestamp']);
		$entity->latitude = $row['latitude'];
		$entity->longitude = $row['longitude'];
		$entity->input = $row['input'];

		$entity->coordinates = new CoordinatesImmutable($entity->latitude, $entity->longitude);
		return $entity;
	}

	public function getLat(): float
	{
		return $this->latitude;
	}

	public function getLon(): float
	{
		return $this->longitude;
	}

	public function getLatLon(string $delimiter = ','): string
	{
		return $this->coordinates->getLatLon($delimiter);
	}
}
