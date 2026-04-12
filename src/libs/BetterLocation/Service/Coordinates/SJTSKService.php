<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\ServicesManager;
use proj4php\Point;
use proj4php\Proj;
use proj4php\Proj4php;

final class SJTSKService extends \App\BetterLocation\Service\AbstractService
{
	const int ID = 64;
	const string NAME = 'S-JTSK';

	public const array TAGS = [
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	public static function getShareText(float $lat, float $lon): ?string
	{
		$proj4 = new Proj4php();

		$wgs84 = new Proj('EPSG:4326', $proj4);
		$sjtsk = new Proj('EPSG:5514', $proj4);

		$point = new Point($lon, $lat);
		$proj4->transform($wgs84, $sjtsk, $point);

		[$x, $y, $z] = $point->toArray();

		if (
			is_float($x) === false
			|| is_float($y) === false
		) {
			return null;
		}

		return sprintf(
			'X: %d, Y: %d',
			round($x),
			round($y),
		);
	}
}
