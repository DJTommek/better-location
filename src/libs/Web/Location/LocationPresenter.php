<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;

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
		Factory::Latte('location.latte', $params);
	}

	private function website($service, $lat, $lon)
	{
		/** @var $service AbstractService */
		$result = [];
		try {
			$result['share'] = $service::getLink($this->lat, $this->lon);
		} catch (NotImplementedException | NotSupportedException $exception) {
		}
		try {
			$result['drive'] = $service::getLink($lat, $lon, true);
		} catch (NotSupportedException | NotImplementedException $exception) {
		}
		try {
			$result['text'] = $service::getShareText($lat, $lon);
		} catch (NotSupportedException | NotImplementedException $exception) {
		}
		return $result;
	}
}

