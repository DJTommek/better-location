<?php declare(strict_types=1);

namespace GlympseApi\Types\Properties;

use Utils\StringUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/objects/data-points#route-property
 */
class RouteProperty extends Property
{
	public static function createFromVariable(\stdClass $variables): self {
		$class = new self();
		foreach ($variables as $key => $value) {
			$name = StringUtils::camelize($key);
			if (in_array($name, ['points'])) {
				$rawPoints = explode(' ', $value);
				$rawFirstPoint = array_shift($rawPoints);
				$firstPoint = new \stdClass(); // @TODO convert to class
				$firstPoint->lat = $rawFirstPoint[0] / 50e6;
				$firstPoint->lng = $rawFirstPoint[1] / 50e6;
				$points = [$firstPoint];
				foreach ($rawPoints as $rawPoint) {
					$pointStd = new \stdClass(); // @TODO convert to class
					$pointStd->lat = $rawPoint[0];
					$pointStd->lng = $rawPoint[1];
					$points[] = $pointStd;
				}
				$value = $points;
			}
			$class->{$name} = $value;
		}
		return $class;
	}

	/** @var ?array Compressed array of location points */
	public $points = null;
	/** @var ?string Source of data (integer value) */
	public $src = null;
	/** @var ?int Distance to the destination (in meters) (optional) */
	public $distance = null;
}
