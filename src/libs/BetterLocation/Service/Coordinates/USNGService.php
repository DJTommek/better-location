<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Coordinates;

use App\BetterLocation\BetterLocationCollection;

/**
 * @deprecated USNG is basically MGRS, keeping for backward compatibility
 * @see MGRSService
 */
final class USNGService extends AbstractService
{
	const TAGS = [];

	const int ID = 13;
	const string NAME = 'USNG';

	public static function findInText(string $text): BetterLocationCollection
	{
		return new BetterLocationCollection();
	}
}
