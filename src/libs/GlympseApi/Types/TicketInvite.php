<?php declare(strict_types=1);

namespace GlympseApi\Types;

use GlympseApi\Types\Properties\TicketProperty;
use Utils\StringUtils;

/**
 * The endpoint to determine type of the item that originally created the invite (ticket, request, etc.) and to retrieve location data and properties for the specified ticket invite.
 *
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/invites/code/get
 */
class TicketInvite extends Type
{
	public static function createFromVariable(\stdClass $variables): self {
		$class = new TicketInvite();
		foreach ($variables as $key => $value) {
			$propertyName = StringUtils::camelize($key);
			// @TODO move properties to $this->property->{propertyName} instead of $this->property{PropertyName}
			if ($key === 'properties') {
				$class->{$propertyName} = TicketProperty::createFromArray($value);
			} else if ($key === 'location') {
				$locations = [];
				foreach ($value as $location) {
					$locations[] = LocationPoint::createFromArray($location);
				}
				$class->{$propertyName} = $locations;
			} else {
				$class->{$propertyName} = $value;
			}
		}
		return $class;
	}

	/** @var string */
	public $type = 'ticket_invite';

	/** @var ?string The code assigned to the invite; also part of the Glympse URL associated with the invite. */
	public $id = null;

	/** @var ?int Sequence number to be specified during the next 'GET invites/:code' call. */
	public $last = null;

	/** @var ?int @TODO Not documented */
	public $next = null;

	/** @var ?int @TODO Not documented */
	public $first = null;

	/** @var ?bool @TODO Not documented */
	public $uncompressed = null;

	/**
	 * @var TicketProperty Latest values of all ticket properties.
	 * @see https://developer.glympse.com/docs/core/api/reference/objects/data-points
	 */
	public $properties = null;

	/**
	 * @var LocationPoint[] All location points uploaded since $next.
	 * @see https://developer.glympse.com/docs/core/api/reference/objects/location-points
	 */
	public $location = [];

	/** @return ?LocationPoint Newest location or null if no location available */
	public function getLastLocation(): ?LocationPoint {
		if (count($this->location) === 0) {
			return null;
		}
		return end($this->location);
	}

	public function getInviteIdUrl() {
		return 'https://glympse.com/' . $this->id;
	}

}
