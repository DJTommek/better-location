<?php declare(strict_types=1);

namespace App\Address;

readonly class Address implements AddressInterface, \Stringable
{
	public string $address;

	public function __construct(
		string $address,
		public ?Country $country = null,
	)
	{
		$this->address = trim($address);
	}

	public function __toString(): string
	{
		return $this->toString();
	}

	public function toString(bool $withCountryEmoji = false): string
	{
		$result = $this->address;
		if ($withCountryEmoji === true && $this->country !== null) {
			$result = $this->country->flagEmoji() . ' ' . $result;
		}

		return $result;
	}

	public function getAddress(): ?Address
	{
		return $this;
	}
}
