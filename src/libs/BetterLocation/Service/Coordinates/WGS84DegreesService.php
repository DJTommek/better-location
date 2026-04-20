<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\Coordinates;

final class WGS84DegreesService extends WGS84AbstractService
{
	const int ID = 10;
	const string NAME = 'WGS84';

	#[\Override]
 public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	#[\Override]
 public static function getShareText(float $lat, float $lon): ?string
	{
		$coords = new Coordinates($lat, $lon);
		return sprintf('%s %F°, %s %F°',
			$coords->getLatHemisphere(), abs($lat),
			$coords->getLonHemisphere(), abs($lon)
		);
	}

	#[\Override]
 protected static function getReCoords(): string
	{
		return '([0-9]{1,3}\.[0-9]{4,20})';
	}
}
