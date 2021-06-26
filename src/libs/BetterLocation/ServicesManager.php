<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\Coordinates\MGRSService;
use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesSecondsService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\FacebookService;
use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GlympseService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\IngressMosaicService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\RopikyNetService;
use App\BetterLocation\Service\SumavaCzService;
use App\BetterLocation\Service\WazeService;
use App\BetterLocation\Service\WhatThreeWordService;
use App\BetterLocation\Service\WikipediaService;
use App\BetterLocation\Service\ZanikleObceCzService;
use App\BetterLocation\Service\ZniceneKostelyCzService;
use App\Config;
use Tracy\Debugger;
use Tracy\ILogger;

class ServicesManager
{
	private $services = [];

	public function __construct()
	{
		$this->services[] = BetterLocationService::class;
		$this->services[] = WGS84DegreesService::class;
		$this->services[] = WGS84DegreesMinutesService::class;
		$this->services[] = WGS84DegreesMinutesSecondsService::class;
		$this->services[] = MGRSService::class;
		$this->services[] = USNGService::class;
		$this->services[] = GoogleMapsService::class;
		$this->services[] = WazeService::class;
		$this->services[] = HereWeGoService::class;
		$this->services[] = OpenStreetMapService::class;
		$this->services[] = FacebookService::class;
		$this->services[] = MapyCzService::class;
		$this->services[] = IngressIntelService::class;
		$this->services[] = OsmAndService::class;
		$this->services[] = DrobnePamatkyCzService::class;
		$this->services[] = OpenLocationCodeService::class;
		$this->services[] = WikipediaService::class;
//		$this->services[] = DuckDuckGoService::class; // currently not supported
		if (Config::isFoursquare()) {
			$this->services[] = FoursquareService::class;
		}
		if (Config::isIngressMosaic()) {
			$this->services[] = IngressMosaicService::class;
		}
		if (is_null(Config::GEOCACHING_COOKIE) === false) {
			$this->services[] = GeocachingService::class;
		}
		if (is_null(Config::W3W_API_KEY) === false) {
			$this->services[] = WhatThreeWordService::class;
		}
		if (Config::isGlympse()) {
			$this->services[] = GlympseService::class;
		}
		$this->services[] = RopikyNetService::class;
		$this->services[] = ZanikleObceCzService::class;
		$this->services[] = ZniceneKostelyCzService::class;
		$this->services[] = SumavaCzService::class;
		$this->services[] = FevGamesService::class;
	}

	public function iterate(string $input): BetterLocationCollection
	{
		foreach ($this->services as $serviceName) {
			$service = new $serviceName($input);
			/** @var $service AbstractService */
			if ($service->isValid()) {
				try {
					$service->process();
				} catch (\Throwable $exception) {
					Debugger::log($exception, Debugger::DEBUG);
				}
				if (count($service->getCollection()) === 0) {
					Debugger::log(sprintf('Input "%s" was validated for "%s", but it was unable to get any valid location.', $input, get_class($service)), ILogger::WARNING);
				}
				return $service->getCollection();
			}
		}
		return new BetterLocationCollection();
	}

	/**
	 * @return AbstractService[]
	 */
	public function getServices(): array
	{
		return $this->services;
	}
}
