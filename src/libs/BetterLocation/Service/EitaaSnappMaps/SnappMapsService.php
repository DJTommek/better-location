<?php declare(strict_types=1);

namespace App\BetterLocation\Service\EitaaSnappMaps;

final class SnappMapsService extends EitaaSnappMapsAbstractService
{
	public const int ID = 61;
	public const string NAME = 'Snapp Maps';
	public const string DOMAIN = 'tile.snappmaps.ir';

	protected static function getDomain(): string
	{
		return self::DOMAIN;
	}
}
