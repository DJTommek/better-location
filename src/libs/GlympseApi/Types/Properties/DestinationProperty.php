<?php declare(strict_types=1);

namespace GlympseApi\Types\Properties;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/objects/data-points#destination-property
 */
class DestinationProperty extends Property
{
	public static function createFromVariable(\stdClass $variables): self {
		$class = new self();
		foreach ($variables as $key => $value) {
			$propertyName = StringUtils::camelize($key);
			if (in_array($propertyName, ['latitude', 'longtitude'])) {
				$value = $value / 10e5; // according documentation it should be 10e6 but it seems to be wrong
			}
			$class->{$propertyName} = $value;
		}
		return $class;
	}

	/** @var ?string Destination name (Optional) */
	public $name = null;
	/** @var ?float Destination latitude */
	public $lat = null;
	/** @var ?float Destination longitude */
	public $lng = null;
}
