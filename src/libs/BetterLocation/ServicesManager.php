<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\BannergressService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\Coordinates\MGRSService;
use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesSecondsService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeCompactService;
use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeMinutesCompactService;
use App\BetterLocation\Service\CoordinatesRender\WGS84DegreeMinutesSecondsCompactService;
use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\DuckDuckGoService;
use App\BetterLocation\Service\EStudankyEuService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FacebookService;
use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GeohashService;
use App\BetterLocation\Service\GlympseService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\HradyCzService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OrganicMapsService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\PrazdneDomyCzService;
use App\BetterLocation\Service\RopikyNetService;
use App\BetterLocation\Service\SumavaCzService;
use App\BetterLocation\Service\SygicService;
use App\BetterLocation\Service\VojenskoCzService;
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
	/** Service can generate location without making any requests */
	public const TAG_GENERATE_OFFLINE = 1;
	/** Service can generate location only by making request to external service  */
	public const TAG_GENERATE_ONLINE = 2;
	/** Service can generate location only by processing via paid service */
	public const TAG_GENERATE_PAID = 3;

	/** Service can generate link to share location */
	public const TAG_GENERATE_LINK_SHARE = 11;
	/** Service can generate link for driving to location */
	public const TAG_GENERATE_LINK_DRIVE = 12;
	/** Service can generate link for generating static image of that location */
	public const TAG_GENERATE_LINK_IMAGE = 13;

	/** Service can generate text representing location */
	public const TAG_GENERATE_TEXT = 0;
	/** Service can generate text representing location without making any requests */
	public const TAG_GENERATE_TEXT_OFFLINE = 21;
	/** Service can generate text representing location only by making request to external service */
	public const TAG_GENERATE_TEXT_ONLINE = 22;
	/** Service can generate text representing location only by processing via paid service */
	public const TAG_GENERATE_TEXT_PAID = 23;

	/** @var AbstractService[] */
	private $services = [];

	public function __construct()
	{
		$this->services[BetterLocationService::ID] = BetterLocationService::class;
		$this->services[WGS84DegreesService::ID] = WGS84DegreesService::class;
		$this->services[WGS84DegreeCompactService::ID] = WGS84DegreeCompactService::class;
		$this->services[WGS84DegreesMinutesService::ID] = WGS84DegreesMinutesService::class;
		$this->services[WGS84DegreeMinutesCompactService::ID] = WGS84DegreeMinutesCompactService::class;
		$this->services[WGS84DegreesMinutesSecondsService::ID] = WGS84DegreesMinutesSecondsService::class;
		$this->services[WGS84DegreeMinutesSecondsCompactService::ID] = WGS84DegreeMinutesSecondsCompactService::class;
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
//		if (Config::isIngressMosaic()) {
//			$this->services[IngressMosaicService::ID] = IngressMosaicService::class;
//		}
		$this->services[BannergressService::ID] = BannergressService::class;
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
		$this->services[HradyCzService::ID] = HradyCzService::class;
		$this->services[VojenskoCzService::ID] = VojenskoCzService::class;
		$this->services[PrazdneDomyCzService::ID] = PrazdneDomyCzService::class;
	}

	public function iterate(string $input): BetterLocationCollection
	{
		foreach ($this->services as $serviceClass) {
			$service = new $serviceClass($input);
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

	/** Process provided text via all services, that have findinText() method */
	public function iterateText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		foreach ($this->services as $serviceClass) {
			try {
				$collection->add($serviceClass::findInText($text));
			} catch (NotSupportedException $exception) {
				// Do nothing
			} catch (\Throwable $exception) {
				Debugger::log($exception, Debugger::DEBUG);
			}
		}
		return $collection;
	}

	/**
	 * @param int[] $tags Return only services, that are tagged by tags provided in this array
	 * @return AbstractService[]
	 */
	public function getServices(array $tags = []): array
	{
		if (empty($tags)) {
			return $this->services;
		} else {
			return array_filter($this->services, function ($service) use ($tags) {
				return !array_diff($tags, $service::TAGS);
			});
		}
	}

	/** @return int[] */
	public function getServicesIds(): array
	{
		return array_keys($this->services);
	}
}
