<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\MaidenheadLocator;
use DJTommek\Coordinates\Coordinates;

final class MaidenheadLocatorService extends AbstractService
{
	const int ID = 57;
	const string NAME = 'Mainhead Locator (QTH)';
	const string NAME_SHORT = 'QTH';

	public function validate(): bool
	{
		return false;
	}

	public static function getShareText(float $lat, float $lon): ?string
	{
		return MaidenheadLocator::fromCoordinates(new Coordinates($lat, $lon))->getCode();
	}
}
