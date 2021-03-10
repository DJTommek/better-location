<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

final class WGS84DegreesMinutesSecondsService extends AbstractService
{
	const RE_COORD = '([0-9]{1,3})[° ]{1,3}([0-9]{1,2})[\' ]{1,3}([0-9]{1,3}\.[0-9]{1,20})[\" ]{0,2}';
	const NAME = 'WGS84 DMS';

	public function process(): void
	{
		$location = static::processWGS84();
		$this->collection->add($location);
	}
}
