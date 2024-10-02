<?php declare(strict_types=1);

namespace App\Utils;

enum UTMFormat {
	case ZONE_COORDS;
	case REFERENCE_SYSTEM;
	case GRID_COORDINATES;
}
