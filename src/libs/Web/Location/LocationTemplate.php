<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\WazeService;

class LocationTemplate
{
	/** @var float */
	public $lat;
	/** @var float */
	public $lon;

	/** @var BetterLocation */
	public $betterLocation;

	public $websites = [];

	public $linkWaze;
	public $linkGoogle;
	public $linkHere;
	public $linkOSM;

	public function __construct(BetterLocation $location)
	{
		$this->betterLocation = $location;
		$this->lat = $location->getLat();
		$this->lon = $location->getLon();

		$this->linkWaze = WazeService::getLink($this->lat, $this->lon);
		$this->linkGoogle = GoogleMapsService::getLink($this->lat, $this->lon);
		$this->linkHere = HereWeGoService::getLink($this->lat, $this->lon);
		$this->linkOSM = OpenStreetMapService::getLink($this->lat, $this->lon);
	}
}

