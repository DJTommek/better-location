<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Dto\AbstractDto;
use App\Utils\Utils;

class GeocodeResponse extends AbstractDto
{
	public const ADDRESS_COMPONENT_COUNTRY = 'country';
	public \stdClass $plus_code;
	/**
	 * @var array<mixed>
	 */
	public array $results;
	public string $status;

	public function getAddress(): ?string
	{
		foreach ($this->results as $result) {
			if (isset($result->formatted_address)) {
				return $result->formatted_address;
			}
		}
		return null;
	}

	/**
	 * Global code: full plus code consisting of alphanumeric characters, completely resolvable offline
	 * Compound code: code consisting of two parts: first is shorter plus code, second is closest big city
	 *
	 * @param bool $compound Return compound code. If not available, returns full plus code instead
	 */
	public function getPlusCode(bool $compound = false): string
	{
		$codes = $this->plus_code;
		if ($compound === true && $codes->compound_code !== null) {
			return $codes->compound_code;
		}
		return $codes->global_code;
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
		return $this->getCountry()?->short_name;
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
