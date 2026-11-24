<?php declare(strict_types=1);

namespace App\IngressPortalnik\Types;

use App\Ingress\Faction;
use App\Ingress\MissionInterface;
use App\Ingress\MissionKind;
use App\Utils\Ingress;

/**
 * Mission as returned by IvoPortalnik API (schema "Mission"), optionally with its waypoints.
 */
final readonly class MissionType implements MissionInterface
{
	/**
	 * @param list<WaypointType> $waypoints Filled only if loaded via mission detail endpoint, otherwise empty.
	 */
	public function __construct(
		public string $guid,
		public ?string $title,
		public ?string $imageUrl,
		public ?string $description,
		public ?string $authorName,
		/** Null if unknown or not supported by this application */
		public ?Faction $authorFaction,
		/** Null if unknown or not supported by this application */
		public ?MissionKind $kind,
		/** False when only the summary (title, image) is known, without waypoints */
		public bool $hasDetails,
		public \DateTimeImmutable $firstSeenAt,
		public \DateTimeImmutable $lastSeenAt,
		public \DateTimeImmutable $updatedAt,
		public array $waypoints = [],
	) {
	}

	/**
	 * @param list<\stdClass> $waypoints
	 */
	public static function createFromVariable(\stdClass $variables, array $waypoints = []): self
	{
		$waypointTypes = array_map(fn(\stdClass $waypoint) => WaypointType::createFromVariable($waypoint), $waypoints);
		usort($waypointTypes, fn(WaypointType $a, WaypointType $b) => $a->position <=> $b->position);

		return new self(
			guid: $variables->guid,
			title: self::trimOrNull($variables->title ?? null),
			imageUrl: $variables->image_url ?? null,
			description: self::trimOrNull($variables->description ?? null),
			authorName: self::trimOrNull($variables->author_name ?? null),
			authorFaction: Faction::tryFrom((string)($variables->author_faction ?? '')),
			kind: MissionKind::tryFrom((string)($variables->type ?? '')),
			hasDetails: (bool)$variables->has_details,
			firstSeenAt: new \DateTimeImmutable($variables->first_seen_at),
			lastSeenAt: new \DateTimeImmutable($variables->last_seen_at),
			updatedAt: new \DateTimeImmutable($variables->updated_at),
			waypoints: $waypointTypes,
		);
	}

	private static function trimOrNull(mixed $value): ?string
	{
		if ($value === null) {
			return null;
		}
		$value = trim((string)$value);
		return $value === '' ? null : $value;
	}

	#[\Override]
	public function getGuid(): string
	{
		return $this->guid;
	}

	#[\Override]
	public function getTitle(): ?string
	{
		return $this->title;
	}

	#[\Override]
	public function getDescription(): ?string
	{
		return $this->description;
	}

	#[\Override]
	public function getAuthorName(): ?string
	{
		return $this->authorName;
	}

	#[\Override]
	public function getAuthorFaction(): ?Faction
	{
		return $this->authorFaction;
	}

	#[\Override]
	public function getKind(): ?MissionKind
	{
		return $this->kind;
	}

	/**
	 * @return list<WaypointType>
	 */
	#[\Override]
	public function getWaypoints(): array
	{
		return $this->waypoints;
	}

	#[\Override]
	public function getStartWaypoint(): ?WaypointType
	{
		foreach ($this->waypoints as $waypoint) {
			if ($waypoint->coordinates !== null) {
				return $waypoint;
			}
		}
		return null;
	}

	#[\Override]
	public function getIntelLink(): string
	{
		return Ingress::generateIntelMissionLink($this->guid);
	}

	#[\Override]
	public function getPrimeLink(): string
	{
		return (string)Ingress::generatePrimeMissionLink($this->guid);
	}

	#[\Override]
	public function getImageLink(?int $size = null): ?string
	{
		$result = $this->imageUrl;
		if ($result !== null && $size !== null) {
			$result .= '=s' . $size;
		}
		return $result;
	}
}
