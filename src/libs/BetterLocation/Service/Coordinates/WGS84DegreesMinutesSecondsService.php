<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\Coordinates;

final class WGS84DegreesMinutesSecondsService extends WGS84AbstractService
{
	const ID = 12;
	const NAME = 'WGS84 DMS';

	public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	public static function getShareText(float $lat, float $lon): ?string
	{
		$coords = new Coordinates($lat, $lon);
		[$degreesLat, $minutesLat, $secondsLat] = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lat);
		[$degreesLon, $minutesLon, $secondsLon] = Coordinates::wgs84DegreesToDegreesMinutesSeconds($lon);
		return sprintf('%s %d° %d\' %.3F", %s %d° %d\' %.3F"',
			$coords->getLatHemisphere(), abs($degreesLat), $minutesLat, $secondsLat,
			$coords->getLonHemisphere(), abs($degreesLon), $minutesLon, $secondsLon
		);
	}

	protected static function getReCoords(): string
	{
		$degSymbol = '(?: ?° ?)';
		$degText = '(?: ?deg ?)';
		$deg = sprintf('(?:(?:%s)|(?:%s))', $degSymbol, $degText);

		return '([0-9]{1,3})' . $deg . '([0-9]{1,2})[\' ]{1,3}([0-9]{1,3}(?:\.[0-9]{1,20})?)[\" ]{0,2}';
	}
}
