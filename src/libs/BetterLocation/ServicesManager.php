<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\AirbnbService;
use App\BetterLocation\Service\AppleMapsService;
use App\BetterLocation\Service\BaladIrService;
use App\BetterLocation\Service\Bannergress\BannergressService;
use App\BetterLocation\Service\Bannergress\OpenBannersService;
use App\BetterLocation\Service\BetterLocationService;
use App\BetterLocation\Service\BookingService;
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
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\FacebookService;
use App\BetterLocation\Service\FevGamesService;
use App\BetterLocation\Service\FirmyCzService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GeohashService;
use App\BetterLocation\Service\GlympseService;
use App\BetterLocation\Service\GoogleEarthService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\GoogleMapsStreetViewGeneratorService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\HradyCzService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\IngressPrimeService;
use App\BetterLocation\Service\KudyZNudyCzService;
use App\BetterLocation\Service\MapyCzPanoramaGeneratorService;
use App\BetterLocation\Service\MapyCzService;
use App\BetterLocation\Service\NeshanOrgService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\OpenStreetMapService;
use App\BetterLocation\Service\OrganicMapsService;
use App\BetterLocation\Service\OsmAndService;
use App\BetterLocation\Service\PrazdneDomyCzService;
use App\BetterLocation\Service\RopikyNetService;
use App\BetterLocation\Service\SumavaCzService;
use App\BetterLocation\Service\SygicService;
use App\BetterLocation\Service\VodniMlynyCz\VodniMlynyCzService;
use App\BetterLocation\Service\VojenskoCzService;
use App\BetterLocation\Service\WaymarkingService;
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

	/** @var array<int,class-string<AbstractService>> */
	private readonly array $services;

	public function __construct()
	{
		$nonIndexedServices = $this->getNonIndexedServices();
		$indexedServices = [];
		foreach ($nonIndexedServices as $nonIndexedService) {
			$indexedServices[$nonIndexedService::ID] = $nonIndexedService;
		}
		$this->services = $indexedServices;
	}

	/**
	 * @return list<class-string<AbstractService>>
	 */
	public function getNonIndexedServices(): array
	{
		$services = [];

		$services[] = BetterLocationService::class;
		$services[] = WGS84DegreesService::class;
		$services[] = WGS84DegreeCompactService::class;
		$services[] = WGS84DegreesMinutesService::class;
		$services[] = WGS84DegreeMinutesCompactService::class;
		$services[] = WGS84DegreesMinutesSecondsService::class;
		$services[] = WGS84DegreeMinutesSecondsCompactService::class;
		$services[] = MGRSService::class;
		$services[] = USNGService::class;
		$services[] = GoogleMapsService::class;
		$services[] = GoogleEarthService::class;
		$services[] = GoogleMapsStreetViewGeneratorService::class;
		$services[] = WazeService::class;
		$services[] = SygicService::class;
		$services[] = HereWeGoService::class;
		$services[] = OpenStreetMapService::class;
		$services[] = NeshanOrgService::class;
		$services[] = BaladIrService::class;
		$services[] = FacebookService::class;
		$services[] = MapyCzService::class;
		$services[] = MapyCzPanoramaGeneratorService::class;
		$services[] = FirmyCzService::class;
		$services[] = IngressIntelService::class;
		$services[] = IngressPrimeService::class;
		$services[] = OsmAndService::class;
		$services[] = DrobnePamatkyCzService::class;
		$services[] = OpenLocationCodeService::class;
		$services[] = GeohashService::class;
		$services[] = OrganicMapsService::class;
		$services[] = WikipediaService::class;
		$services[] = AirbnbService::class;
		$services[] = BookingService::class;
		$services[] = DuckDuckGoService::class;
		$services[] = AppleMapsService::class;
		if (Config::isFoursquare()) {
			$services[] = FoursquareService::class;
		}
//		if (Config::isIngressMosaic()) {
//			$services[] = IngressMosaicService::class;
//		}
		$services[] = BannergressService::class;
		$services[] = OpenBannersService::class;
		if (Config::isGeocaching()) {
			$services[] = GeocachingService::class;
		}
		$services[] = WaymarkingService::class;
		if (Config::isW3W()) {
			$services[] = WhatThreeWordService::class;
		}
		if (Config::isGlympse()) {
			$services[] = GlympseService::class;
		}
		$services[] = RopikyNetService::class;
		$services[] = ZanikleObceCzService::class;
		$services[] = ZniceneKostelyCzService::class;
		$services[] = SumavaCzService::class;
		$services[] = FevGamesService::class;
		$services[] = EStudankyEuService::class;
		$services[] = HradyCzService::class;
		$services[] = VojenskoCzService::class;
		$services[] = PrazdneDomyCzService::class;
		$services[] = KudyZNudyCzService::class;
		$services[] = VodniMlynyCzService::class;

		return $services;
	}

	public function iterate(string $input): BetterLocationCollection
	{
		foreach ($this->services as $serviceClass) {
			$service = new $serviceClass($input);
			/** @var $service AbstractService */
			if ($service->isValid()) {
				try {
					$service->process();
				} catch (NotSupportedException | InvalidLocationException) {
					// Do nothing
				} catch (\Throwable $exception) {
					Debugger::log($exception, Debugger::ERROR);
					// @phpstan-ignore-next-line
					assert(false, 'Investigate and fix this error: ' . $exception->getMessage());
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
			/** @var class-string<AbstractService> $serviceClass */
			try {
				$subCollection = $serviceClass::findInText($text);
				$collection->add($subCollection);
			} catch (NotSupportedException | InvalidLocationException) {
				// Do nothing
			} catch (\Throwable $exception) {
				Debugger::log($exception, Debugger::DEBUG);
			}
		}
		return $collection;
	}

	/**
	 * @param int[] $tags Return only services, that are tagged by tags provided in this array
	 * @return array<int,class-string<AbstractService>>
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
