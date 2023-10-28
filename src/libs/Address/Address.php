<?php declare(strict_types=1);

namespace App\Address;

readonly class Address implements AddressInterface, \Stringable
{
	/**
	 * @var non-empty-string
	 */
	public string $address;

	public function __construct(
		string $address,
		public ?Country $country = null,
	)
	{
		$addressTrim = trim($address);
		if ($addressTrim === '') {
			throw new \InvalidArgumentException('Empty address is not allowed');
		}
		$this->address = $addressTrim;
	}

	/**
	 * @return non-empty-string
	 */
	public function __toString(): string
	{
		return $this->toString();
	}

	/**
	 * @return non-empty-string
	 */
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
