<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Telegram;

use App\BetterLocation\Service\AbstractService;

final class LocationService extends AbstractService
{
	const ID = 50;
	const NAME = 'Telegram location';

	const TYPE_CLASSIC = 'Classic';
	const TYPE_LIVE = 'Live';
	const TYPE_VENUE = 'Venue';

	public static function getConstants(): array
	{
		return [
			self::TYPE_CLASSIC,
			self::TYPE_LIVE,
			self::TYPE_VENUE,
		];
	}
}
