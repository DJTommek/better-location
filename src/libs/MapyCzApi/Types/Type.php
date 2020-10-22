<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @version 2020-10-22
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
abstract class Type
{
	protected static function cast(\stdClass $instance)
	{
		return unserialize(sprintf(
			'O:%d:"%s"%s',
			\strlen(static::class),
			static::class,
			strstr(strstr(serialize($instance), '"'), ':')
		));
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
