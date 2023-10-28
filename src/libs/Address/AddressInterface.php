<?php declare(strict_types=1);

namespace App\Address;

interface AddressInterface
{
	public function getAddress(): ?Address;
}
