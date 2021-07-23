<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\WazeService;

class BetterLocationMessageSettings
{
	/** @var AbstractService[] $services */
	public $linkServices = [
		BetterLocationService::class,
		GoogleMapsService::class,
		MapyCzService::class,
		DuckDuckGoService::class,
		WazeService::class,
		HereWeGoService::class,
		OpenStreetMapService::class,
	];
	/** @var AbstractService[] $services */
	public $buttonServices = [
		GoogleMapsService::class,
		WazeService::class,
		HereWeGoService::class,
		OsmAndService::class,
	];
	public $screenshotLinkService = MapyCzService::class;
	public $address = true;
}
