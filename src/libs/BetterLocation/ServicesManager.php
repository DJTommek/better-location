<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\AbstractServiceNew;
use App\BetterLocation\Service\IngressIntelService;
use App\BetterLocation\Service\MapyCzServiceNew;

class ServicesManager
{
	/** @var AbstractServiceNew[] */
	private $services = [];

	public function __construct()
	{
		$this->services[] = MapyCzServiceNew::class;
		$this->services[] = IngressIntelService::class;
	}

	public function iterate(string $input): BetterLocationCollection
	{
		foreach ($this->services as $serviceName) {
			/** @var $service AbstractServiceNew */
			$service = new $serviceName($input);
			if ($service->isValid()) {
				$service->process();
				return $service->getCollection();
			}
		}
		return new BetterLocationCollection();
	}
}
