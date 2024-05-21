<?php declare(strict_types=1);

namespace App\Web\Favorites;

use App\BetterLocation\BetterLocation;
use unreal4u\TelegramAPI\Telegram;

readonly class JsLocationDto
{
	public float $lat;
	public float $lon;
	/** @var array{float, float} */
	public array $coords;
	public string $hash;
	public string $key;
	public ?string $address;

	public function __construct(
		BetterLocation $location,
	) {
		$this->lat = $location->getLat();
		$this->lon = $location->getLon();
		$this->coords = [$location->getLat(), $location->getLon()];
		$this->hash = $location->getCoordinates()->hash();
		$this->key = $location->getLatLon();
		$this->address = $location->getAddress();
	}
}

