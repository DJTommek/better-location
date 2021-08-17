<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocationCollection;
use App\Web\LayoutTemplate;

class LocationsTemplate extends LayoutTemplate
{
	/** @var BetterLocationCollection */
	public $collection;
	public $websites = [];

	public function prepare(BetterLocationCollection $collection, array $websites)
	{
		$this->collection = $collection;
		$this->websites = $websites;
	}
}

