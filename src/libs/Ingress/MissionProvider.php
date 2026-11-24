<?php declare(strict_types=1);

namespace App\Ingress;

/**
 * Source of Ingress mission data. Implementations are interchangeable, so it is easy to switch between external APIs.
 *
 * @see \App\IngressPortalnik\Client
 */
interface MissionProvider
{
	/**
	 * @param string $guid Ingress mission GUID, eg. "b9d1285bdf184780a79f1a00b68e893e.1c"
	 * @return ?MissionInterface Null if mission with given GUID does not exist.
	 */
	public function getMissionByGUID(string $guid): ?MissionInterface;
}
