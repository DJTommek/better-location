<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Factory;
use App\Utils\Coordinates;
use App\Utils\DateImmutableUtils;
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
		$regex = '/^' . Coordinates::RE_BASIC . '(;' . Coordinates::RE_BASIC . ')+$/';
		$input = $_GET['coords'] ?? '';
		if ($input && preg_match($regex, $input)) {
			foreach (explode(';', $input) as $coords) {
				list($lat, $lon) = explode(',', $coords);
				if (Coordinates::isLat($lat) && Coordinates::isLon($lon)) {
					$location = BetterLocation::fromLatLon(Strict::floatval($lat), Strict::floatval($lon));
					$location->generateAddress();
					$location->generateDateTimeZone();
					$this->collection->add($location);
				}
			}
		}
		$this->collection->deduplicate();
		$this->collection->fillElevations();

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

