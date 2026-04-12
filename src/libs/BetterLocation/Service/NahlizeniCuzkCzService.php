<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesImmutable;

final class NahlizeniCuzkCzService extends AbstractService
{
	const int ID = 65;
	const string NAME = 'Nahlížení ČÚZK';

	const string LINK = 'https://nahlizenidokn.cuzk.gov.cz/';

	public const array TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		[$x, $y] = Utils::WgsToSjtsk(new CoordinatesImmutable($lat, $lon));

		return self::LINK . sprintf('MapaIdentifikace.aspx?l=KN&x=%1$d&y=%2$d', round($x), round($y));
	}
}
