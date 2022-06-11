<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\ServicesManager;

final class SygicService extends AbstractService
{
	const ID = 4;
	const NAME = 'Sygic';

	const LINK = 'https://sygic.com';
	const DRIVE_LINK = 'https://go.sygic.com';
	const SHARE_LINK = 'https://maps.sygic.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): string
	{
		if ($drive) {
			return sprintf('%s/navi/directions?to=%F,%F', self::DRIVE_LINK, $lat, $lon);
		} else {
			return sprintf('%1$s/#/?map=17,%2$F,%3$F&address=%2$F,%3$F', self::SHARE_LINK, $lat, $lon);
		}
	}
}
