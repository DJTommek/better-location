<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractServiceNew;
use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GlympseService;
use App\BetterLocation\Service\GoogleMapsService;
use App\BetterLocation\Service\HereWeGoService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\MapyCzServiceNew;
use App\Config;
use Tracy\Debugger;
use Tracy\ILogger;

class ServicesManager
{
	/** @var AbstractServiceNew[] */
	private $services = [];

	public function __construct()
	{
		$this->services[] = GoogleMapsService::class;
		$this->services[] = HereWeGoService::class;
		$this->services[] = MapyCzServiceNew::class;
		$this->services[] = IngressIntelService::class;
		$this->services[] = DrobnePamatkyCzService::class;
//		$this->services[] = DuckDuckGoService::class; // currently not supported
		if (Config::isFoursquare()) {
			$this->services[] = FoursquareService::class;
		}
		if (is_null(Config::GEOCACHING_COOKIE) === false) {
			$this->services[] = GeocachingService::class;
		}
		if (Config::isGlympse()) {
			$this->services[] = GlympseService::class;
		}
	}

	public function iterate(string $input): BetterLocationCollection
	{
		foreach ($this->services as $serviceName) {
			/** @var $service AbstractServiceNew */
			$service = new $serviceName($input);
			if ($service->isValid()) {
				try {
					$service->process();
				} catch (\Throwable $exception) {
					Debugger::log($exception, Debugger::DEBUG);
				}
				return $service->getCollection();
			}
		}
		return new BetterLocationCollection();
	}
}
