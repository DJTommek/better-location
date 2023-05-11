<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Utils\DateImmutableUtils;
use App\Web\LayoutTemplate;

class LocationsTemplate extends LayoutTemplate
{
	public BetterLocationCollection $collection;
	/** @var array<BetterLocation> */
	public array $locations;
	/** @var array<string,array<array{name: string, share?: string, drive?: string, text?: string, static?: string}>> */
	public array $websites = [];
	/** @var array<array{float, float}> */
	public array $allCoords = [];

	/** @var array<array<float>> Calculated distances between all points */
	public array $distances = [];
	/** @TODO maybe INF constant should be used instead of PHP_FLOAT_MAX */
	public float $distanceSmallest = PHP_FLOAT_MAX;
	public float $distanceGreatest = 0;

	/** @var array<array{lat: float, lon: float, coords: array{float, float}, hash: string, key: string, address: ?string}> */
	public array $collectionJs = [];
	/** @var string Text representation of now in UTC */
	public string $nowUtcText;

	public bool $showingTimezoneData = false;
	public bool $showingElevation = false;

	/**
	 * @param array<string,array<array{name: string, share?: string, drive?: string, text?: string, static?: string}>> $websites
	 */
	public function prepare(BetterLocationCollection $collection, array $websites): void
	{
		$this->collection = $collection;
		$this->locations = $collection->getLocations();
		$this->collectionJs = array_map(function (BetterLocation $location) {
			return [
				'lat' => $location->getLat(),
				'lon' => $location->getLon(),
				'coords' => [$location->getLat(), $location->getLon()],
				'hash' => $location->getCoordinates()->hash(),
				'key' => $location->__toString(),
				'address' => $location->getAddress(),
			];
		}, $collection->getLocations());
		$this->allCoords = array_map(function (BetterLocation $location) {
			return [$location->getLat(), $location->getLon()];
		}, $collection->getLocations());
		$this->websites = $websites;
		$this->nowUtcText = DateImmutableUtils::nowUtc()->format(DATE_ISO8601);
		$this->calculateDistances();
	}

	private function calculateDistances(): void
	{
		foreach ($this->locations as $keyVertical => $locationVertical) {
			$this->distances[$keyVertical] = [];
			foreach ($this->locations as $keyHorizontal => $locationHorizontal) {
				if ($keyVertical === $keyHorizontal) {
					$distance = null;
				} else {
					$distance = round($locationVertical->getCoordinates()->distance($locationHorizontal->getCoordinates()), 6);
					$this->distanceGreatest = max($distance, $this->distanceGreatest);
					$this->distanceSmallest = min($distance, $this->distanceSmallest);
				}
				$this->distances[$keyVertical][$keyHorizontal] = $distance;
			}
		}
	}
}

