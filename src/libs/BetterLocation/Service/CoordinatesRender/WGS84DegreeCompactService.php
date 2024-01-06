<?php declare(strict_types=1);

namespace App\BetterLocation\Service\CoordinatesRender;

use App\Utils\Coordinates;

final class WGS84DegreeCompactService extends AbstractService
{
	const ID = 36;
	const NAME = 'WGS84 Compact';

	public static function getShareText(float $lat, float $lon): ?string
	{
		return (new Coordinates($lat, $lon))->key();
	}
}
