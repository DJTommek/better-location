<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

final class WGS84DegreesSecondsService extends WGS84AbstractService
{
	const ID = 55;
	const NAME = 'WGS84 DS';

	public function process(): void
	{
		$location = self::processWGS84();
		$this->collection->add($location);
	}

	protected static function getReCoords(): string
	{
		$degSymbol = '(?: ?° ?)';
		$degText = '(?: ?deg ?)';
		$deg = sprintf('(?:(?:%s)|(?:%s))', $degSymbol, $degText);

		return '([0-9]{1,3})' . $deg . '([0-9]{1,4}(?:\.[0-9]{1,20})?) ?"';
	}
}
