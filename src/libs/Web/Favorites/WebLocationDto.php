<?php declare(strict_types=1);

namespace App\Web\Favorites;

use App\BetterLocation\BetterLocation;
use App\Geonames\Types\TimezoneType;
use App\Utils\Coordinates;
use unreal4u\TelegramAPI\Telegram;

readonly class WebLocationDto
{
	public Coordinates $coords;
	public float $lat;
	public float $lon;
	public string $latLon;
	public string $hash;
	public bool $hasAddress;
	public string $address;
	public ?TimezoneType $timezoneData;

	public function __construct(
		private BetterLocation $betterLocation,
		public string $titleHtml,
		public string $title,
	) {
		$this->coords = $this->betterLocation->getCoordinates();
		$this->lat = $this->coords->getLat();
		$this->lon = $this->coords->getLon();
		$this->latLon = $this->coords->getLatLon();
		$this->hash = md5($this->latLon);
		$this->hasAddress = $this->betterLocation->hasAddress();
		$this->address = $this->betterLocation->getAddress() ?? 'Unknown address';
		$this->timezoneData = $this->betterLocation->getTimezoneData();
	}
}

