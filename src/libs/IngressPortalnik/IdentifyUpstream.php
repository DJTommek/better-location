<?php declare(strict_types=1);

namespace App\IngressPortalnik;

/**
 * What IvoPortalnik should do in /portals/identify when portal is not identified from its local database.
 */
enum IdentifyUpstream: string
{
	/** Ask external sources during the request (slowest, most complete) */
	case Wait = 'wait';
	/** Answer right away from local data and ask external sources afterwards */
	case Background = 'background';
	/** Use local data only */
	case Skip = 'skip';
}
