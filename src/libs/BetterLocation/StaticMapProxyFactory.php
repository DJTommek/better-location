<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Repository\StaticMapCacheRepository;
use DJTommek\Coordinates\CoordinatesInterface;

class StaticMapProxyFactory
{
	public function __construct(
		private readonly StaticMapCacheRepository $staticMapCacheRepository,
	)
	{
	}

	public function fromCacheId(string $cacheId): ?StaticMapProxy
	{
		$result = new StaticMapProxy($this->staticMapCacheRepository);
		$result->initFromCacheId($cacheId);
		return $result->exists() ? $result : null;
	}

	/**
	 * Load static map image based on provided single location.
	 */
	public function fromLocation(CoordinatesInterface $input): ?StaticMapProxy
	{
		return $this->fromLocations([$input]);
	}

	/**
	 * Load static map image based on provided input (single or multiple locations).
	 *
	 * @param array<CoordinatesInterface>|BetterLocationCollection $locations
	 */
	public function fromLocations(array|BetterLocationCollection $locations): ?StaticMapProxy
	{
		$result = new StaticMapProxy($this->staticMapCacheRepository);
		$result->initFromLocations($locations);
		return $result->exists() ? $result : null;
	}
}
