<?php declare(strict_types=1);

namespace App\Foursquare\Types;

class VenueLocationType extends Type
{
	/** @var string */
	public $address;
	/** @var string */
	public $crossStreet;
	/** @var float */
	public $lat;
	/** @var float */
	public $lng;
	/** @var VenueLabeledLatLngs[] */
	public $labeledLatLngs;
	/** @var string */
	public $postalCode;
	/** @var string */
	public $cc;
	/** @var string */
	public $neighborhood;
	/** @var string */
	public $city;
	/** @var string */
	public $state;
	/** @var string */
	public $country;
	/** @var string[] */
	public $formattedAddress;

	public function getFormattedAddress()
	{
		return join(' ', $this->formattedAddress);
	}
}
