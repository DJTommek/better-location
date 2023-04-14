<?php declare(strict_types=1);

namespace App\Utils;

/**
 * @deprecated Use \DJTommek\Coordinates\CoordinatesInterface
 */
interface CoordinatesInterface extends \DJTommek\Coordinates\CoordinatesInterface
{
	/**
	 * Returns latitude in range from -90 to 90
	 */
	public function getLat(): float;

	/**
	 * Returns longitude in range from -180 to 180
	 */
	public function getLon(): float;

	/**
	 * Returns latitude and longitude in format 'lat,lon'
	 */
	public function key(): string;
}
