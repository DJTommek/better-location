<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @method static self cast(\stdClass $stdClass)
 *
 * @version 2020-10-26
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
class LookupBoxPlaceType extends Type
{
	public function hasCoords(): bool
	{
		return isset($this->mark->lat) && isset($this->mark->lon);
	}

	public function getLat(): float
	{
		return $this->mark->lat;
	}

	public function getLon(): float
	{
		return $this->mark->lon;
	}
}
