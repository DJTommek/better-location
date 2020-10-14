<?php declare(strict_types=1);

namespace GlympseApi\Types\Properties;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/objects/data-points#properties
 */
class TicketProperty extends Property
{
	public static function createFromArray(array $properties): self {
		$class = new self();
		foreach ($properties as $property) {
			$propertyName = StringUtils::camelize($property->n);
			$propertyValue = $property->v;
			if (is_null($propertyValue)) {
				// skip any processing and save value as-is
			} else if (in_array($propertyName, ['app'])) {
				$propertyValue = AppProperty::createFromVariable($propertyValue);
			} else if (in_array($propertyName, ['eta'])) {
				$propertyValue = EtaProperty::createFromVariable($propertyValue);
			} else if (in_array($propertyName, ['startTime', 'endTime'])) {
				$propertyValue = new \DateTimeImmutable(sprintf('@%d', $propertyValue / 1000));
			} else if (in_array($propertyName, ['destination'])) {
				$propertyValue = DestinationProperty::createFromVariable($propertyValue);
			} else if (in_array($propertyName, ['route'])) {
				$propertyValue = RouteProperty::createFromVariable($propertyValue);
			}
			$class->{$propertyName} = $propertyValue;
		}
		return $class;
	}

	/** @var ?AppProperty Description of application that owns ticket at the moment. Property changes every time, when ticket ownership is changed. The blob is associated with application API key. */
	public $app = null;
	/** @var ?string Nickname of ticket sender. The server appends this property in response to users/:id/update_avatar endpoint call. */
	public $avatar = null;
	/** @var ?string Mode in which ticket was sent. The property is appended by client right after ticket creation and is changed every time, when user toggles the setting. Values: regular or savings. */
	public $batteryMode = null;
	/** @var ?string Contains identifier of the card (string value) the ticket belongs to. */
	public $cardId = null;
	/** @var ?bool Indicates that sender is done making changes to the ticket, flushed all its caches and will not modify the ticket anymore. Values true or false. */
	public $completed = null;
	/** @var ?DestinationProperty Ticket destination. */
	public $destination = null;
	/** @var ?\DateTimeImmutable Ticket expire time. The server appends it originally in response to users/:id/create_ticket and later updates in response to tickets/:id/update. */
	public $endTime = null;
	/** @var ?EtaProperty ETA value. */
	public $eta = null;
	/** @var ?string Ticket message. */
	public $message = null;
	/** @var ?string Nickname of ticket sender. The server appends this property in response to users/:id/update. */
	public $name = null;
	/** @var ?string User ID of ticket creator. Never changes. */
	public $owner = null;
	/** @var ?string String value indicating the state of customer journey (ie: pre, eta, live, arrived, feedback, etc...). */
	public $phase = null;
	/** @var ?RouteProperty Predicted route. */
	public $route = null;
	/** @var ?string Ticket start time. Never changes. */
	public $startTime = null;
	/** @var ?string Specifies the travelling mode under which track points are generated. Note that a ticket may have multiple instances of this property occur while it is active (i.e. hand-off from default to airline - both client and server potentially affecting this property). Timestamps should be used to line up which mode a particular location is generated. */
	public $travelMode = null;

	/** @var ?int *Not documented */
	public $xoaMode = null;
}
