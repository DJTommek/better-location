<?php declare(strict_types=1);

namespace App\BetterLocation\Service\VodniMlynyCz;

use App\Utils\Strict;
use DJTommek\Coordinates\CoordinatesImmutable;

class Estate
{
	public readonly CoordinatesImmutable $coords;

	public function __construct(
		public readonly float $lat,
		public readonly float $lng,
		public readonly int $id,
		public readonly string $name,
		public readonly string $icon,
	) {
		$this->coords = new CoordinatesImmutable($lat, $lng);
	}

	/**
	 * @param array<\stdClass|array<mixed>> $batch
	 * @return array<int, Estate>
	 */
	public static function fromResponseBatch(array $batch): array
	{
		$result = [];
		foreach ($batch as $raw) {
			$estate = self::fromResponse($raw);
			assert(isset($result[$estate->id]) === false, sprintf('Estate ID %d already exists.', $estate->id));
			$result[$estate->id] = $estate;
		}
		return $result;
	}

	public static function fromResponse(\stdClass $raw): self
	{
		return new self(
			Strict::floatval($raw->lat),
			Strict::floatval($raw->lng),
			Strict::intval($raw->id),
			$raw->name,
			$raw->icon,
		);
	}
}
