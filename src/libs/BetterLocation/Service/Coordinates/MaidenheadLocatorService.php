<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\Utils\MaidenheadLocator;
use DJTommek\Coordinates\Coordinates;

final class MaidenheadLocatorService extends AbstractService
{
	const ID = 57;
	const NAME = 'Mainhead Locator (QTH)';
	const NAME_SHORT = 'QTH';

	public function validate(): bool
	{
		return false;
	}

	public static function getShareText(float $lat, float $lon): ?string
	{
		return MaidenheadLocator::fromCoordinates(new Coordinates($lat, $lon))->getCode();
	}
}
