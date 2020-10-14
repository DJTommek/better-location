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

	public $type = 'ticket_invite';
	/** @var ?string */
	public $id = null;
	/** @var ?int */
	public $last = null;
	/** @var ?int */
	public $next = null;
	/** @var ?int */
	public $first = null;
	/** @var ?bool */
	public $uncompressed = null;

	/** @var TicketProperty */
	public $properties = null;
	/** @var LocationPoint[] */
	public $location = [];

	public function getLastLocation(): LocationPoint {
		return end($this->location);
	}
}
