<?php declare(strict_types=1);

namespace App\BetterLocation\Service\EitaaSnappMaps;

final class EitaaMapsService extends EitaaSnappMapsAbstractService
{
	public const ID = 60;
	public const NAME = 'Eitaa Maps';
	public const DOMAIN = 'map.eitaa.com';

	protected static function getDomain(): string
	{
		return self::DOMAIN;
	}
}
