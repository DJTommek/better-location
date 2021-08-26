<?php declare(strict_types=1);

namespace App\Web\Location;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\WazeService;
use App\Geonames\Types\TimezoneType;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\DateImmutableUtils;
use App\Web\LayoutTemplate;

class LocationTemplate extends LayoutTemplate
{
	/** @var float */
	public $lat;
	/** @var float */
	public $lon;
	/** @var ?bool */
	public $isFavourite;

	/** @var BetterLocation */
	public $betterLocation;

	public $websites = [];

	public $linkWaze;
	public $linkGoogle;
	public $linkHere;
	public $linkOSM;
	public $linkTG;

	/** @var ?TimezoneType */
	public $timezoneData;

	/** @var string Text representation of now in UTC */
	public $nowUtcText;

	public function prepare(BetterLocation $location, array $websites)
	{
		$this->betterLocation = $location;
		$this->timezoneData = $this->betterLocation->getTimezoneData();
		$this->websites = $websites;

		$this->lat = $location->getLat();
		$this->lon = $location->getLon();

		if ($this->login->isLogged()) {
			$this->isFavourite = $this->user->getFavourite($this->lat, $this->lon) !== null;
		}

		$this->linkWaze = WazeService::getShareLink($this->lat, $this->lon);
		$this->linkGoogle = GoogleMapsService::getShareLink($this->lat, $this->lon);
		$this->linkHere = HereWeGoService::getShareLink($this->lat, $this->lon);
		$this->linkOSM = OpenStreetMapService::getShareLink($this->lat, $this->lon);
		$this->linkTG = TelegramHelper::generateStartLocation($this->lat, $this->lon);

		$this->nowUtcText = DateImmutableUtils::nowUtc()->format(DATE_ISO8601);
	}
}

