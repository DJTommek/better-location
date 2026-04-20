<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\EitaaSnappMaps;

use App\BetterLocation\Service\EitaaSnappMaps\EitaaMapsService;

final class EitaaSnappMapsTest extends EitaaSnappMapsAbstract
{
	#[\Override]
 protected function getServiceClass(): string
	{
		return EitaaMapsService::class;
	}

	#[\Override]
 protected static function getDomain(): string
	{
		return EitaaMapsService::DOMAIN;
	}

	#[\Override]
 protected static function isValidExtraProvider(): array
	{
		return [];
	}

	#[\Override]
 protected static function processExtraProvider(): array
	{
		return [];
	}
}
