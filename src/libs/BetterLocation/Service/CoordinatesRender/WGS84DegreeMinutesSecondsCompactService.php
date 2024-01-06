<?php declare(strict_types=1);

namespace App\BetterLocation\Service\CoordinatesRender;

use App\Utils\Coordinates;

final class WGS84DegreeMinutesSecondsCompactService extends AbstractService
{
	const ID = 38;
	const NAME = 'WGS84 DMS Compact';

	public static function getShareText(float $lat, float $lon): ?string
	{
		[$degreesLat, $minutesLat, $secondsLat] = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lat);
		[$degreesLon, $minutesLon, $secondsLon] = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lon);
		return sprintf('%d°%d\'%.3F,%d°%d\'%.3F',
			$degreesLat, $minutesLat, $secondsLat,
			$degreesLon, $minutesLon, $secondsLon
		);
	}
}
