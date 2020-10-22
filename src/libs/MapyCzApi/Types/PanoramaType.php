<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @version 2020-10-22
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
class PanoramaType extends Type
{
	public static function cast(\stdClass $instance): self
	{
		return parent::cast($instance);
	}
}
