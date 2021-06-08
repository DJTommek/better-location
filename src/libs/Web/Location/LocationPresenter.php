<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\Service\AbstractService;

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
		$params = new \App\Web\Location\LocationTemplate();
		$params->betterLocation = \App\BetterLocation\BetterLocation::fromLatLon($this->lat, $this->lon);

		$manager = new \App\BetterLocation\ServicesManager();
		foreach ($manager->getServices() as $service) {
			$params->websites[$service::NAME] = $this->website($service, $this->lat, $this->lon);
		}

//	$params->websites[\App\BetterLocation\Service\DrobnePamatkyCzService::NAME] = [
//		'share' => \App\BetterLocation\Service\DrobnePamatkyCzService::getLink($lat, $lon),
//		'drive' => \App\BetterLocation\Service\DrobnePamatkyCzService::getLink($lat, $lon, True),
//	];
//	$params->websites[\App\BetterLocation\Service\GeocachingService::NAME] = \App\BetterLocation\Service\GeocachingService::getLink($lat, $lon);

		dump($params->betterLocation);
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

