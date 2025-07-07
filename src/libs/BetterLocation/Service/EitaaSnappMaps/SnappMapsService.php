<?php declare(strict_types=1);

namespace App\BetterLocation\Service\EitaaSnappMaps;

final class SnappMapsService extends EitaaSnappMapsAbstractService
{
	public const ID = 61;
	public const NAME = 'Eitaa Maps';
	public const DOMAIN = 'tile.snappmaps.ir';

	protected static function getDomain(): string
	{
		return self::DOMAIN;
	}
}
