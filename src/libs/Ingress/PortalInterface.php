<?php declare(strict_types=1);

namespace App\Ingress;

use DJTommek\Coordinates\CoordinatesInterface;

/**
 * Ingress portal, independent on API which provided its data.
 */
interface PortalInterface extends CoordinatesInterface
{
	public function getGuid(): string;

	/**
	 * @return ?string Null if portal name is unknown.
	 */
	public function getName(): ?string;

	/**
	 * @return ?string Null if portal address is unknown.
	 */
	public function getAddress(): ?string;

	/**
	 * @param ?int $size Append size parameter to image URL. See https://developers.google.com/people/image-sizing
	 */
	public function getImageLink(?int $size = null): ?string;

	/** Link to open portal in Ingress Intel map */
	public function getIntelLink(): string;

	/** Link to open portal in Ingress Prime application */
	public function getPrimeLink(): string;

	/** Link to open portal in Niantic Lightship geospatial browser */
	public function getLightshipLink(): string;
}
