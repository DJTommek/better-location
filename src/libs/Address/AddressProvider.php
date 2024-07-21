<?php declare(strict_types=1);

namespace App\Address;

use DJTommek\Coordinates\CoordinatesInterface;

interface AddressProvider
{
	public function reverse(CoordinatesInterface $coordinates): ?AddressInterface;
}
