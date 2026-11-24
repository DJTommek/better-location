<?php declare(strict_types=1);

namespace App\Ingress;

use DJTommek\Coordinates\CoordinatesInterface;

/**
 * Single waypoint of Ingress mission, independent on API which provided its data.
 */
interface WaypointInterface
{
	/** 0-based */
	public function getPosition(): int;

	public function getKind(): ?WaypointKind;

	public function getObjective(): ?WaypointObjective;

	/**
	 * @return ?string Null if waypoint title is unknown.
	 */
	public function getTitle(): ?string;

	public function isHidden(): bool;

	/**
	 * @return ?CoordinatesInterface Null if location of waypoint is unknown (eg. hidden waypoint)
	 */
	public function getCoordinates(): ?CoordinatesInterface;

	/**
	 * @return ?string GUID of portal of this waypoint, null if unknown or waypoint is not portal.
	 */
	public function getPortalGuid(): ?string;
}
