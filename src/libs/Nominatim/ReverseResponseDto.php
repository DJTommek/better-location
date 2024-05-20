<?php declare(strict_types=1);

namespace App\Nominatim;

use App\Address\Address;
use App\Address\AddressInterface;
use App\Address\Country;
use App\Dto\AbstractDto;
use DJTommek\Coordinates\CoordinatesInterface;

/**
 * @link https://nominatim.org/release-docs/latest/api/Output/#place-output
 */
class ReverseResponseDto extends AbstractDto implements AddressInterface, CoordinatesInterface
{
	/**
	 * Reference to the Nominatim internal database ID
	 * place_id is not a persistent id
	 *
	 * @link https://nominatim.org/release-docs/latest/api/Output/#place_id-is-not-a-persistent-id
	 */
	public readonly int $place_id;
	public readonly string $licence;
	/**
	 * Reference to the OSM object
	 *
	 * @link https://nominatim.org/release-docs/latest/api/Output/#osm-reference
	 */
	public readonly int $osm_id;
	/**
	 * Reference to the OSM object
	 *
	 * @link https://nominatim.org/release-docs/latest/api/Output/#osm-reference
	 */
	public readonly string $osm_type;
	/** Longitude of the centroid of the object */
	public readonly float $lat;
	/** Longitude of the centroid of the object */
	public readonly float $lon;
	/** Key of the main OSM tag */
	public readonly string $class;
	/** Value of the main OSM tag */
	public readonly string $type;
	/** Search rank of the object */
	public readonly int $place_rank;
	/** Computed importance rank */
	public readonly float $importance;
	public readonly string $addresstype;
	/** Localised name of the place */
	public readonly string $name;
	/** Full comma-separated address */
	public readonly string $display_name;

	/**
	 * Dictionary of address details
	 * Address splitted into various sections (country_code, country, region, state, village, ....)
	 *
	 * @var array<string, string>
	 * @link https://nominatim.org/release-docs/latest/api/Output/#addressdetails
	 */
	public readonly array $address;
	/**
	 * Area of corner coordinates
	 *
	 * @var array{float, float, float, float}
	 * @link https://nominatim.org/release-docs/latest/api/Output/#boundingbox
	 */
	public readonly array $boundingbox;

	public function getAddress(): ?Address
	{
		$country = null;
		$countryCode = $this->address['country_code'] ?? null;
		if ($countryCode !== null) {
			$country = new Country($countryCode, $this->address['country']);
		}

		return new Address($this->display_name, $country);
	}

	public function getLat(): float
	{
		return $this->lat;
	}

	public function getLon(): float
	{
		return $this->lon;
	}

	public function getLatLon(string $delimiter = ','): string
	{
		return sprintf('%F%s%F', $this->getLat(), $delimiter,  $this->getLon());
	}

	public function set(string $name, mixed $value): void
	{
		$this->{$name} = match ($name) {
			'lat', 'lon' => (float)$value,
			'boundingbox' => array_map('floatval', $value),
			default => $value,
		};
	}

}
