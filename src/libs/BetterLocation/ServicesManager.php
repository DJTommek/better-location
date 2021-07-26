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
use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\EStudankyEuService;
use App\BetterLocation\Service\FacebookService;
use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GeohashService;
use App\BetterLocation\Service\GlympseService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\IngressMosaicService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OrganicMapsService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\RopikyNetService;
use App\BetterLocation\Service\SumavaCzService;
use App\BetterLocation\Service\SygicService;
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
		$this->services[BetterLocationService::ID] = BetterLocationService::class;
		$this->services[WGS84DegreesService::ID] = WGS84DegreesService::class;
		$this->services[WGS84DegreesMinutesService::ID] = WGS84DegreesMinutesService::class;
		$this->services[WGS84DegreesMinutesSecondsService::ID] = WGS84DegreesMinutesSecondsService::class;
		$this->services[MGRSService::ID] = MGRSService::class;
		$this->services[USNGService::ID] = USNGService::class;
		$this->services[GoogleMapsService::ID] = GoogleMapsService::class;
		$this->services[WazeService::ID] = WazeService::class;
		$this->services[SygicService::ID] = SygicService::class;
		$this->services[HereWeGoService::ID] = HereWeGoService::class;
		$this->services[OpenStreetMapService::ID] = OpenStreetMapService::class;
		$this->services[FacebookService::ID] = FacebookService::class;
		$this->services[MapyCzService::ID] = MapyCzService::class;
		$this->services[IngressIntelService::ID] = IngressIntelService::class;
		$this->services[OsmAndService::ID] = OsmAndService::class;
		$this->services[DrobnePamatkyCzService::ID] = DrobnePamatkyCzService::class;
		$this->services[OpenLocationCodeService::ID] = OpenLocationCodeService::class;
		$this->services[GeohashService::ID] = GeohashService::class;
		$this->services[OrganicMapsService::ID] = OrganicMapsService::class;
		$this->services[WikipediaService::ID] = WikipediaService::class;
		$this->services[DuckDuckGoService::ID] = DuckDuckGoService::class;
		if (Config::isFoursquare()) {
			$this->services[FoursquareService::ID] = FoursquareService::class;
		}
		if (Config::isIngressMosaic()) {
			$this->services[IngressMosaicService::ID] = IngressMosaicService::class;
		}
		if (is_null(Config::GEOCACHING_COOKIE) === false) {
			$this->services[GeocachingService::ID] = GeocachingService::class;
		}
		if (is_null(Config::W3W_API_KEY) === false) {
			$this->services[WhatThreeWordService::ID] = WhatThreeWordService::class;
		}
		if (Config::isGlympse()) {
			$this->services[GlympseService::ID] = GlympseService::class;
		}
		$this->services[RopikyNetService::ID] = RopikyNetService::class;
		$this->services[ZanikleObceCzService::ID] = ZanikleObceCzService::class;
		$this->services[ZniceneKostelyCzService::ID] = ZniceneKostelyCzService::class;
		$this->services[SumavaCzService::ID] = SumavaCzService::class;
		$this->services[FevGamesService::ID] = FevGamesService::class;
		$this->services[EStudankyEuService::ID] = EStudankyEuService::class;
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

	/** @return int[] */
	public function getServicesIds(): array
	{
		return array_keys($this->services);
	}
}
