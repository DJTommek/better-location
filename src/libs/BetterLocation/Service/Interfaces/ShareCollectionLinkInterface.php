<?php declare(strict_types=1);

namespace App\BetterLocation\Service\Interfaces;

use App\BetterLocation\BetterLocationCollection;

/**
 * Service is able to generate link representing multiple locations at once.
 */
interface ShareCollectionLinkInterface
{
	public static function getShareCollectionLink(BetterLocationCollection $collection): ?string;
}
