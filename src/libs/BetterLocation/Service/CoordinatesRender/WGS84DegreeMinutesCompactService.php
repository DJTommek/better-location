<?php declare(strict_types=1);

namespace App\BetterLocation\Service\CoordinatesRender;

use App\Utils\Coordinates;

final class WGS84DegreeMinutesCompactService extends AbstractService
{
	const ID = 37;
	const NAME = 'WGS84 DM Compact';

	public static function getShareText(float $lat, float $lon): string
	{
		list($degreesLat, $minutesLat) = Coordinates::wgs84DegreesToDegreesMinutes($lat);
		list($degreesLon, $minutesLon) = Coordinates::wgs84DegreesToDegreesMinutes($lon);
		return sprintf('%d°%.3F,%d°%.3F',
			$degreesLat, $minutesLat,
			$degreesLon, $minutesLon
		);
	}
}
