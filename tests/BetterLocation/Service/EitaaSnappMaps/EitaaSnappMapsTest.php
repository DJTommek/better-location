<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\EitaaSnappMaps;

use App\BetterLocation\Service\EitaaSnappMaps\EitaaMapsService;

final class EitaaSnappMapsTest extends EitaaSnappMapsAbstract
{
	protected function getServiceClass(): string
	{
		return EitaaMapsService::class;
	}

	protected static function getDomain(): string
	{
		return EitaaMapsService::DOMAIN;
	}

	protected static function isValidExtraProvider(): array
	{
		return [];
	}

	protected static function processExtraProvider(): array
	{
		return [];
	}
}
