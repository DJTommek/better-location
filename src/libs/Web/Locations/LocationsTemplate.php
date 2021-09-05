<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Web\LayoutTemplate;

class LocationsTemplate extends LayoutTemplate
{
	/** @var BetterLocationCollection */
	public $collection;
	public $websites = [];
	public $allCoords = [];
	public $collectionJs = [];

	public function prepare(BetterLocationCollection $collection, array $websites)
	{
		$this->collection = $collection;
		$this->collectionJs = array_map(function(BetterLocation $location) {
			return [
				'lat' => $location->getLat(),
				'lon' => $location->getLon(),
				'coords' => [$location->getLat(), $location->getLon()],
				'hash' => $location->getCoordinates()->hash(),
				'key' => $location->__toString(),
				'address' => $location->getAddress(),
			];
		}, $collection->getLocations());
		$this->allCoords = array_map(function(BetterLocation $location) {
			return [$location->getLat(), $location->getLon()];
		}, $collection->getLocations());
		$this->websites = $websites;
	}
}
