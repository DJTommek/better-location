<?php declare(strict_types=1);

namespace App\Ingress;

enum WaypointKind: string
{
	case Portal = 'portal';
	case FieldTrip = 'field_trip';
}
