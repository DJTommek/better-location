<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @method static self cast(\stdClass $stdClass)
 *
 * @version 2020-10-22
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
class PlaceType extends Type
{
	public function getLat(): float
	{
		return $this->mark->lat;
	}

	public function getLon(): float
	{
		return $this->mark->lon;
	}
}
