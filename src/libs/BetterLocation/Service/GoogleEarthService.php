<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use DJTommek\Coordinates\Coordinates;

final class GoogleEarthService extends AbstractService
{
	const ID = 50;
	const NAME = 'Google Earth';

	/**
	 * https://earth.google.com/web/@50.087451,14.420671,0a,100000.00d,0y,0h,0t,0r
	 *                               \__lat__/ \__lon__/    \_*Note1_/
	 *                                \__coordinates__/
	 *
	 * * Note 1: Distance from Earth in meters (zoom level)
	 */
	const LINK = 'https://earth.google.com/web/@%1$F,%2$F,0a,100000.00d,35y,0h,0t,0r';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		return sprintf(self::LINK, $lat, $lon,);
	}


	public function isValid(): bool
	{
		if ($this->url === null) {
			return false;
		}

		if ($this->url->getDomain(3) !== 'earth.google.com') {
			return false;
		}

		$this->data->basicCoords = null;
		// https://earth.google.com/web/@50.05966962,14.46562341
		// https://earth.google.com/web/search/prague/@50.05966962,14.46562341,272.89275321a,47773.07656964d,35y,0.00000344h,0t,0r
		if (preg_match('/\/@(-?[0-9.]+),(-?[0-9.]+)/', $this->url->getPath(), $matches)) {
			$this->data->basicCoords = Coordinates::safe($matches[1], $matches[2]);
		}

		if ($this->data->basicCoords !== null) {
			return true;
		}

		return false;
	}

	public function process(): void
	{
		if (isset($this->data->basicCoords)) {
			$coords = $this->data->basicCoords;
			$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
			$this->collection->add($location);
		}
	}
}
