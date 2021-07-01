<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\Coordinates;

final class WGS84DegreesMinutesSecondsService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3})[° ]{1,3}([0-9]{1,2})[\' ]{1,3}([0-9]{1,3}(?:\.[0-9]{1,20})?)[\" ]{0,2}';
	const NAME = 'WGS84 DMS';

	public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	public static function getShareText(float $lat, float $lon): string
	{
		$coords = new Coordinates($lat, $lon);
		list($degreesLat, $minutesLat, $secondsLat) = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lat);
		list($degreesLon, $minutesLon, $secondsLon) = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lon);
		return sprintf('%s %d° %d\' %.3F", %s %d° %d\' %.3F"',
			$coords->getLatHemisphere(), abs($degreesLat), $minutesLat, $secondsLat,
			$coords->getLonHemisphere(), abs($degreesLon), $minutesLon, $secondsLon
		);
	}
}
