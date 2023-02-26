<?php

namespace App\Pluginer;

use App\BetterLocation\BetterLocation;
use App\Utils\CoordinatesInterface;

class InputOutputLocation
{
	public ?string $address;
	public string $prefix;
	public CoordinatesInterface $coordinates;

	public function __construct(BetterLocation $location)
	{
		$this->address = $location->getAddress();
		$this->prefix = $location->getPrefixMessage();
		$this->coordinates = $location->getCoordinates();
	}
}
