<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\Nominatim\NominatimException;

class LocationPresenter
{
	private $lat;
	private $lon;

	public function __construct(float $lat, float $lon)
	{
		$this->lat = $lat;
		$this->lon = $lon;
	}

	public function render()
	{
		$location = BetterLocation::fromLatLon($this->lat, $this->lon);
		$location->generateAddress();
		$params = new LocationTemplate($location);

		$manager = new ServicesManager();
		foreach ($manager->getServices() as $service) {
			$params->websites[$service::NAME] = $this->website($service, $this->lat, $this->lon);
		}

//	$params->websites[\App\BetterLocation\Service\DrobnePamatkyCzService::NAME] = [
//		'share' => \App\BetterLocation\Service\DrobnePamatkyCzService::getLink($lat, $lon),
//		'drive' => \App\BetterLocation\Service\DrobnePamatkyCzService::getLink($lat, $lon, True),
//	];
//	$params->websites[\App\BetterLocation\Service\GeocachingService::NAME] = \App\BetterLocation\Service\GeocachingService::getLink($lat, $lon);

//		dump($params->betterLocation);
		\App\Factory::Latte('location.latte', $params);

	}

	private function website($service, $lat, $lon)
	{
		/** @var $service AbstractService */
		$links = [];
		try {
			$links['share'] = $service::getLink($this->lat, $this->lon);
		} catch (\App\BetterLocation\Service\Exceptions\NotImplementedException $exception) {
		} catch (\App\BetterLocation\Service\Exceptions\NotSupportedException $exception) {
		}
		try {
			$links['drive'] = $service::getLink($lat, $lon, true);
		} catch (\App\BetterLocation\Service\Exceptions\NotSupportedException $exception) {

		} catch (\App\BetterLocation\Service\Exceptions\NotImplementedException $exception) {
		}
		return $links;

	}
}

