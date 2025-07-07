<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\EitaaSnappMaps;

use App\BetterLocation\Service\EitaaSnappMaps\SnappMapsService;

final class SnappSnappMapsTest extends EitaaSnappMapsAbstract
{
	protected function getServiceClass(): string
	{
		return SnappMapsService::class;
	}

	protected static function getDomain(): string
	{
		return SnappMapsService::DOMAIN;
	}

	protected static function isValidExtraProvider(): array
	{
		return [
			'English style of map' => [true, 'https://tile.snappmaps.ir/styles/en-snapp-style/#11.85/35.65342/51.35701/-22.4/12'],
			'Random style of map' => [true, 'https://tile.snappmaps.ir/styles/abcd-abcd/#11.85/35.65342/51.35701/-22.4/12'],
		];
	}

	protected static function processExtraProvider(): array
	{
		return [
			'English style of map' => [35.65342, 51.35701, 'https://tile.snappmaps.ir/styles/en-snapp-style/#11.85/35.65342/51.35701/-22.4/12'],
			'Random style of map' => [45.703960, 9.662384, 'https://tile.snappmaps.ir/styles/abcd-abcd/#11.85/45.703960/9.662384/-22.4/12'],
		];
	}
}
