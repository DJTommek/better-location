<?php declare(strict_types=1);

namespace App\BetterLocation\Service\EitaaSnappMaps;

final class EitaaMapsService extends EitaaSnappMapsAbstractService
{
	public const int ID = 60;
	public const string NAME = 'Eitaa Maps';
	public const string DOMAIN = 'map.eitaa.com';

	protected static function getDomain(): string
	{
		return self::DOMAIN;
	}
}
