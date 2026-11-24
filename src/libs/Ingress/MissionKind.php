<?php declare(strict_types=1);

namespace App\Ingress;

/**
 * Type of mission, how waypoints are presented to agent.
 */
enum MissionKind: string
{
	/** Waypoints must be completed in order */
	case Sequential = 'sequential';
	/** Waypoints can be completed in any order */
	case AnyOrder = 'any_order';
	/** Waypoints must be completed in order and next waypoint is revealed only after completing previous one */
	case Hidden = 'hidden';

	public function label(): string
	{
		return match ($this) {
			self::Sequential => 'sequential',
			self::AnyOrder => 'any order',
			self::Hidden => 'hidden',
		};
	}
}
