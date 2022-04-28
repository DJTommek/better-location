<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Factory;
use App\Utils\Coordinates;
use App\Utils\DateImmutableUtils;
use App\Utils\General;
use App\Utils\Strict;
use App\Web\MainPresenter;
use Nette\Utils\Json;

class LocationsPresenter extends MainPresenter
{
	/** @var BetterLocationCollection */
	private $collection;
	/** @var array Multidimensional array of all structures, where is possible to generate something (share link, drive link, ...) */
	private $services = [];
	/** @var string */
	private $format = 'html';
	/** @var string */
	private $nowFileText;


	public function __construct()
	{
		$this->template = new LocationsTemplate();
		$this->collection = new BetterLocationCollection();
		$this->nowFileText = DateImmutableUtils::nowUtc()->format(Config::DATETIME_FILE_FORMAT);
		parent::__construct();
	}

	public function action()
	{
		$regex = '/^' . Coordinates::RE_BASIC . '(;' . Coordinates::RE_BASIC . ')*$/';
		$input = $_GET['coords'] ?? '';
		if ($input && preg_match($regex, $input)) {
			foreach (explode(';', $input) as $coords) {
				list($lat, $lon) = explode(',', $coords);
				if (Coordinates::isLat($lat) && Coordinates::isLon($lon)) {
					$this->collection->add(BetterLocation::fromLatLon(Strict::floatval($lat), Strict::floatval($lon)));
				}
			}
		}
		$this->collection->deduplicate();

		if ($this->collection->count() && isset($_GET['action'])) {
			if ($this->login->isLogged()) {
				switch ($_GET['action']) {
					case 'add':
						foreach ($this->collection as $location) {
							$this->user->addFavourite($location, BetterLocation::generateFavouriteName($location->getLat(), $location->getLon()));
						}
						break;
					case 'delete':
						foreach ($this->collection as $location) {
							$this->user->deleteFavourite($location);
						}
						break;
				}
			}
			$this->redirect($this->collection->getLink());
		}

		if (in_array(General::globalGetToBool('address'), [true, null], true)) { // if not set, default is true
			$this->collection->fillAddresses();
		}
		if (General::globalGetToBool('datetimezone') === true) {
			$this->collection->fillDatetimeZone();
		}
		if (General::globalGetToBool('elevation') === true) {
			$this->collection->fillElevations();
		}

		foreach ($this->collection as $location) {
			$manager = new ServicesManager();
			$services = [];
			foreach ($manager->getServices() as $service) {
				$services[] = $this->website($service, $location->getLat(), $location->getLon());
			}
			$services = array_values(array_filter($services));
			$this->services[$location->key()] = $services;
		}
		$this->format = mb_strtolower($_GET['format'] ?? 'html');
	}

	public function render(): void
	{
		if (count($this->collection)) {
			$this->template->prepare($this->collection, $this->services);
			switch ($this->format) {
				case 'html':
				default;
					Factory::Latte('locations.latte', $this->template);
					break;
				case 'gpx':
					$this->fileGpx();
					break;
				case 'json':
					$this->json();
					break;
				case 'kml':
					$this->fileKml();
					break;
			}
		} else {
			Factory::Latte('locationsError.latte', $this->template);
		}
	}

	public function json(): void
	{
		$result = new \stdClass();
		$result->locations = array_map(function (BetterLocation $location) {
			$resultLocation = new \stdClass();
			$resultLocation->lat = $location->getLat();
			$resultLocation->lon = $location->getLon();
			$resultLocation->elevation = $location->getCoordinates()->getElevation();
			$resultLocation->address = $location->getAddress();
			$resultLocation->services = $this->services[$location->key()];
			return $resultLocation;
		}, $this->collection->getLocations());
		header('Content-Type: application/json');
		header('Access-Control-Allow-Origin: *');
		die(Json::encode($result));
	}

	public function fileGpx(): void
	{
		header(sprintf('Content-Disposition: attachment; filename="BetterLocation_%d_locations_%s.gpx"', count($this->collection), $this->nowFileText));
		Factory::Latte('locationsGpx.latte', $this->template);
	}

	public function fileKml(): void
	{
		header(sprintf('Content-Disposition: attachment; filename="BetterLocation_%d_locations_%s.kml"', count($this->collection), $this->nowFileText));
		Factory::Latte('locationsKml.latte', $this->template);
	}

	private function website($service, float $lat, float $lon)
	{
		/** @var $service AbstractService */
		$result = [];
		if ($service::hasTag(ServicesManager::TAG_GENERATE_LINK_SHARE)) {
			$result['share'] = $service::getLink($lat, $lon);
		}
		if ($service::hasTag(ServicesManager::TAG_GENERATE_LINK_DRIVE)) {
			$result['drive'] = $service::getLink($lat, $lon, true);
		}
		if ($service::hasTag(ServicesManager::TAG_GENERATE_TEXT)) {
			$result['text'] = $service::getShareText($lat, $lon);
		}
		if ($service::hasTag(ServicesManager::TAG_GENERATE_LINK_IMAGE)) {
			$result['static'] = $service::getScreenshotLink($lat, $lon);
		}

		if ($result !== []) {
			$result['name'] = $service::NAME;
		}
		return $result;
	}
}

