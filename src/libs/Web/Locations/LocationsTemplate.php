<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Utils\DateImmutableUtils;
use App\Web\LayoutTemplate;

class LocationsTemplate extends LayoutTemplate
{
	public BetterLocationCollection $collection;
	/** array<BetterLocation> */
	public array $locations;
	public $websites = [];
	public $allCoords = [];

	/** @var array<array<float>> Calculated distances between all points */
	public $distances = [];
	/** @TODO maybe INF constant should be used instead of PHP_FLOAT_MAX */
	public float $distanceSmallest = PHP_FLOAT_MAX;
	public float $distanceGreatest = 0;

	public array $collectionJs = [];
	/** @var string Text representation of now in UTC */
	public $nowUtcText;

	public bool $showingTimezoneData = false;
	public bool $showingElevation = false;

	public function prepare(BetterLocationCollection $collection, array $websites)
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

