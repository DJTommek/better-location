<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

final class WGS84DegreesService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3}\.[0-9]{1,20})';
	const NAME = 'WGS84';

	public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	public static function getShareText(float $lat, float $lon): string
	{
		return sprintf('%F°, %F°', $lat, $lon);
	}
}
