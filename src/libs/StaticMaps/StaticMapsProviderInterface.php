<?php declare(strict_types=1);

namespace App\StaticMaps;

use DJTommek\Coordinates\CoordinatesInterface;

interface StaticMapsProviderInterface
{
	/** @param CoordinatesInterface[] $markers */
	public function generatePrivateUrl(array $markers): string;
}
