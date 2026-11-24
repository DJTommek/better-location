<?php declare(strict_types=1);

namespace App\Ingress;

/**
 * What agent has to do on mission waypoint.
 */
enum WaypointObjective: string
{
	case Hack = 'hack';
	case CaptureOrUpgrade = 'capture_or_upgrade';
	case CreateLink = 'create_link';
	case CreateField = 'create_field';
	case InstallMod = 'install_mod';
	case TakePhoto = 'take_photo';
	case ViewFieldTrip = 'view_field_trip';
	case EnterPassphrase = 'enter_passphrase';
}
