<?php declare(strict_types=1);

namespace App\Ingress;

/**
 * Ingress mission, independent on API which provided its data.
 */
interface MissionInterface
{
	public function getGuid(): string;

	/**
	 * @return ?string Null if mission title is unknown.
	 */
	public function getTitle(): ?string;

	/**
	 * @return ?string Null if mission description is unknown.
	 */
	public function getDescription(): ?string;

	/**
	 * @return ?string Null if author is unknown.
	 */
	public function getAuthorName(): ?string;

	public function getAuthorFaction(): ?Faction;

	public function getKind(): ?MissionKind;

	/**
	 * @return list<WaypointInterface> In mission order. Empty if waypoints are unknown.
	 */
	public function getWaypoints(): array;

	/**
	 * First waypoint with known location, usually start of the mission.
	 */
	public function getStartWaypoint(): ?WaypointInterface;

	/**
	 * @param ?int $size Append size parameter to image URL. See https://developers.google.com/people/image-sizing
	 */
	public function getImageLink(?int $size = null): ?string;

	/** Link to open mission in Ingress Intel map */
	public function getIntelLink(): string;

	/** Link to open mission in Ingress Prime application */
	public function getPrimeLink(): string;
}
