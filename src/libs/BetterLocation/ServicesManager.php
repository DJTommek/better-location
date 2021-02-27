<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractServiceNew;
use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\FoursquareService;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\MapyCzServiceNew;
use Tracy\Debugger;

class ServicesManager
{
	/** @var AbstractServiceNew[] */
	private $services = [];

	public function __construct()
	{
		$this->services[] = MapyCzServiceNew::class;
		$this->services[] = IngressIntelService::class;
		$this->services[] = DrobnePamatkyCzService::class;
//		$this->services[] = DuckDuckGoService::class; // currently not supported
		$this->services[] = FoursquareService::class;
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
