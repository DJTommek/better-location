<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\Utils\Coordinates;
use App\Utils\Strict;
use App\Web\MainPresenter;

class LocationsPresenter extends MainPresenter
{
	/** @var BetterLocationCollection */
	private $collection;
	/** @var array Multidimensional array of all structures, where is possible to generate something (share link, drive link, ...) */
	private $services = [];

	public function __construct()
	{
		$this->template = new LocationsTemplate();
		$this->collection = new BetterLocationCollection();
		parent::__construct();
	}

	public function action()
	{
		$regex = '/^' . Coordinates::RE_BASIC . '(;' . Coordinates::RE_BASIC . ')+$/';
		$input = $_GET['coords'] ?? '';
		if ($input && preg_match($regex, $input)) {
			foreach (explode(';', $input) as $coords) {
				list($lat, $lon) = explode(',', $coords);
				if (Coordinates::isLat($lat) && Coordinates::isLon($lon)) {
					$location = BetterLocation::fromLatLon(Strict::floatval($lat), Strict::floatval($lon));
					$location->generateAddress();
					$this->collection->add($location);
				}
			}
		}
		$this->collection->deduplicate();

		foreach ($this->collection as $location) {
			$manager = new ServicesManager();
			$services = [];
			foreach ($manager->getServices() as $service) {
				$services[] = $this->website($service, $location->getLat(), $location->getLon());
			}
			$services = array_values(array_filter($services));
			$key = $location->__toString();
			$this->services[$key] = $services;
		}
	}

	public function render(): void
	{
		if (count($this->collection)) {
			$this->template->prepare($this->collection, $this->services);
			Factory::Latte('locations.latte', $this->template);
		} else {
			Factory::Latte('locationsError.latte', $this->template);
		}
	}

	private function website($service, float $lat, float $lon)
	{
		/** @var $service AbstractService */
		$result = [];
		try {
			$result['share'] = $service::getLink($lat, $lon);
		} catch (NotSupportedException $exception) {
		}
		try {
			$result['drive'] = $service::getLink($lat, $lon, true);
		} catch (NotSupportedException $exception) {
		}
		try {
			$result['text'] = $service::getShareText($lat, $lon);
		} catch (NotSupportedException $exception) {
		}
		if ($result !== []) {
			$result['name'] = $service::NAME;
		}
		return $result;
	}
}

