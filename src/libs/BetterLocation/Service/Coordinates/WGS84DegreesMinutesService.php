<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\Coordinates;

final class WGS84DegreesMinutesService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3})[° ]{1,3}([0-9]{1,3}\.[0-9]{1,20}) ?\'?';
	const NAME = 'WGS84 DM';

	public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	public static function getShareText(float $lat, float $lon): string
	{
		list($degreesLat, $minutesLat) = Coordinates::wgs84DegreesToDegreesMinutes($lat);
		list($degreesLon, $minutesLon) = Coordinates::wgs84DegreesToDegreesMinutes($lon);
		return sprintf('%d° %.5F\', %d° %.5F\'', $degreesLat, $minutesLat, $degreesLon, $minutesLon);
	}
}
