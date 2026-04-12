<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\ServicesManager;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesImmutable;

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
		[$x, $y] = Utils::WgsToSjtsk(new CoordinatesImmutable($lat, $lon));

		return sprintf('X: %d, Y: %d', round($x), round($y));
	}
}
