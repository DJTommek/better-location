<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Address\Address;
use App\Address\AddressInterface;
use App\Address\Country;
use App\Dto\AbstractDto;

class GeocodeResponse extends AbstractDto implements AddressInterface
{
	public const ADDRESS_COMPONENT_COUNTRY = 'country';
	public \stdClass $plus_code;
	/**
	 * @var array<mixed>
	 */
	public array $results;
	public string $status;

	public function getAddress(): ?Address
	{
		$address = $this->findFormattedAddress();
		if ($address === null) {
			return null;
		}
		$country = $this->findCountry();

		return new Address($address, $country);
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

	/**
	 * Search within response for most precise location.
	 */
	private function findFormattedAddress(): ?string
	{
		foreach ($this->results as $result) {
			if (isset($result->formatted_address)) {
				return $result->formatted_address;
			}
		}
		return null;
	}

	/**
	 * Search within response to find country attribute
	 */
	private function findCountry(): ?Country
	{
		foreach ($this->results as $result) {
			foreach ($result->address_components as $addressComponent) {
				if (in_array(self::ADDRESS_COMPONENT_COUNTRY, $addressComponent->types, true)) {
					return new Country($addressComponent->short_name, $addressComponent->long_name);
				}
			}
		}
		return null;
	}
}
