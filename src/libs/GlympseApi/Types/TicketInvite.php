<?php declare(strict_types=1);

namespace GlympseApi\Types;

use GlympseApi\Types\Properties\DestinationProperty;
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
				foreach ($value as $property) {
					$propertyName = StringUtils::camelize('property_' . $property->n);
					$propertyValue = $property->v;
					if (in_array($propertyName, ['propertyStartTime', 'propertyEndTime'])) {
						$propertyValue = new \DateTimeImmutable(sprintf('@%d', $propertyValue / 1000));
					} else if (in_array($propertyName, ['propertyEta'])) { // @TODO convert to EtaProperty
						$propertyValue->eta_ts = new \DateTimeImmutable(sprintf('@%d', $propertyValue->eta_ts / 1000));
						$propertyValue->eta = new \DateInterval(sprintf('PT%dS', $propertyValue->eta));
					} else if (in_array($propertyName, ['propertyDestination'])) {
						$propertyValue = DestinationProperty::createFromVariable($propertyValue);
					}
					$class->{$propertyName} = $propertyValue;
				}
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
	/** @var ?\DateTimeImmutable */
	public $propertyStartTime = null;
	/** @var ?\DateTimeImmutable */
	public $propertyEndTime = null;
	/** @var ?string */
	public $propertyOwner = null;
	/** @var ?string */
	public $propertyName = null;
	/** @var ?string */
	public $propertyAvatar = null;
	/** @var ?string */
	public $propertyMessage = null;
	/** @var ?string @TODO */
	public $propertyDestination = null;
	/** @var ?int */
	public $propertyXoaMode = null;
	/** @var ?string */
	public $propertyBatteryMode = null;
	/** @var ?string */
	public $propertyTravelMode = null;
	/** @var ?string @TODO */
	public $propertyEta = null;
	/** @var ?string */
	public $propertyRoute = null;
	/** @var LocationPoint[] */
	public $location = [];

	public function getLastLocation(): LocationPoint {
		return end($this->location);
	}
}
