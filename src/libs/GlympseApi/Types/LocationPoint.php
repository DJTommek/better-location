<?php declare(strict_types=1);

namespace GlympseApi\Types;

use Utils\DateImmutableUtils;

/**
 * @version 2020-10-14
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 * @see https://developer.glympse.com/docs/core/api/reference/objects/location-points
 */
class LocationPoint extends Type
{

	public static function createFromArray(array $location): self {
//		list($timestamp, $latitude, $longtitude, $speed, $heading, $elevation, $horizontalAccuracy, $verticalAccuracy) = $location;
		list($timestamp, $latitude, $longtitude, $speed, $heading, $elevation) = $location;
		$class = new self();
		$class->timestamp = DateImmutableUtils::fromTimestampMs($timestamp);
		$class->latitude = $latitude / 10e5; // according documentation it should be 10e6 but it seems to be wrong
		$class->longtitude = $longtitude / 10e5; // according documentation it should be 10e6 but it seems to be wrong
		$class->speed = $speed;
		$class->heading = $heading;
		$class->elevation = $elevation;
//		$class->horizontalAccuracy = $horizontalAccuracy;
//  	$class->verticalAccuracy = $verticalAccuracy;
		return $class;
	}

	/** @var ?\DateTimeImmutable */
	public $timestamp = null;
	/** @var ?int */
	public $latitude = null;
	/** @var ?int */
	public $longtitude = null;
	/** @var ?int */
	public $speed = null;
	/** @var ?int */
	public $heading = null;
	/** @var ?int */
	public $elevation = null;
	/** @var ?int */
	public $horizontalAccuracy = null;
	/** @var ?int */
	public $verticalAccuracy = null;
}
