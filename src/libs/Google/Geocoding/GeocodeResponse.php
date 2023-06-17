<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Utils\Utils;

class GeocodeResponse
{
	public const ADDRESS_COMPONENT_COUNTRY = 'country';

	public \stdClass $plus_code;
	/**
	 * @var array<mixed>
	 */
	public array $results;
	public string $status;

	/**
	 * @param \stdClass|array<mixed> $raw
	 */
	public static function cast(\stdClass|array $raw): self
	{
		$result = new self();
		foreach ((array)$raw as $name => $value) {
			$result->{$name} = $value;
		}
		return $result;
	}

	public function getAddress(): ?string
	{
		foreach ($this->results as $result) {
			if (isset($result->formatted_address)) {
				return $result->formatted_address;
			}
		}
		return null;
	}

	public function getAddressWithFlag(): ?string
	{
		$address = $this->getAddress();
		if ($address === null) {
			return null;
		}

		$flag = $this->getCountryFlagEmoji();
		if ($flag === null) {
			return $address;
		}

		return $flag . ' ' . $address;
	}

	public function getCountryCode(): ?string
	{
		return $this->getCountry()->short_name;
	}

	private function getCountry(): ?\stdClass
	{
		foreach ($this->results as $result) {
			foreach ($result->address_components as $addressComponent) {
				if (in_array(self::ADDRESS_COMPONENT_COUNTRY, $addressComponent->types, true)) {
					return $addressComponent;
				}
			}
		}
		return null;
	}

	public function getCountryFlagEmoji(): ?string
	{
		$countryCode = $this->getCountryCode();
		if ($countryCode === null) {
			return null;
		}

		return Utils::flagEmojiFromCountryCode($countryCode);
	}

	public function __toString(): string
	{
		return $this->getAddress();
	}
}
