<?php declare(strict_types=1);

namespace App\IngressPortalnik\Types;

use App\Ingress\WaypointInterface;
use App\Ingress\WaypointKind;
use App\Ingress\WaypointObjective;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;

/**
 * Single waypoint of a mission as returned by IvoPortalnik API (schema "Waypoint").
 */
final readonly class WaypointType implements WaypointInterface
{
	public function __construct(
		/** 0-based */
		public int $position,
		/** Null if unknown or not supported by this application */
		public ?WaypointKind $kind,
		/** Null if unknown or not supported by this application */
		public ?WaypointObjective $objective,
		public ?string $title,
		public bool $hidden,
		/** Null if location of waypoint is unknown (eg. hidden waypoint) */
		public ?CoordinatesInterface $coordinates,
		/** Portal of this waypoint, if known */
		public ?string $portalGuid,
	) {
	}

	public static function createFromVariable(\stdClass $variables): self
	{
		$lat = $variables->lat ?? null;
		$lng = $variables->lng ?? null;
		$title = trim((string)($variables->title ?? ''));

		return new self(
			position: (int)$variables->position,
			kind: WaypointKind::tryFrom((string)($variables->type ?? '')),
			objective: WaypointObjective::tryFrom((string)($variables->objective ?? '')),
			title: $title === '' ? null : $title,
			hidden: (bool)$variables->hidden,
			coordinates: ($lat === null || $lng === null) ? null : new Coordinates((float)$lat, (float)$lng),
			portalGuid: $variables->portal_guid ?? null,
		);
	}

	#[\Override]
	public function getPosition(): int
	{
		return $this->position;
	}

	#[\Override]
	public function getKind(): ?WaypointKind
	{
		return $this->kind;
	}

	#[\Override]
	public function getObjective(): ?WaypointObjective
	{
		return $this->objective;
	}

	#[\Override]
	public function getTitle(): ?string
	{
		return $this->title;
	}

	#[\Override]
	public function isHidden(): bool
	{
		return $this->hidden;
	}

	#[\Override]
	public function getCoordinates(): ?CoordinatesInterface
	{
		return $this->coordinates;
	}

	#[\Override]
	public function getPortalGuid(): ?string
	{
		return $this->portalGuid;
	}
}
