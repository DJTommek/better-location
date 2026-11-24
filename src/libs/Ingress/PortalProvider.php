<?php declare(strict_types=1);

namespace App\Ingress;

/**
 * Source of Ingress portal data. Implementations are interchangeable, so it is easy to switch between external APIs.
 *
 * @see \App\IngressLanchedRu\Client
 * @see \App\IngressPortalnik\Client
 */
interface PortalProvider
{
	/**
	 * Find portal located on given coordinates.
	 *
	 * @return ?PortalInterface Null if no portal is on given coordinates.
	 */
	public function getPortalByCoords(float $lat, float $lng): ?PortalInterface;

	/**
	 * @param string $guid Ingress portal GUID, eg. "ed64a5b3c8fc3d61973a863b3667b116.16"
	 * @return ?PortalInterface Null if portal with given GUID does not exist.
	 */
	public function getPortalByGUID(string $guid): ?PortalInterface;
}
