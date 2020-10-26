<?php declare(strict_types=1);

namespace MapyCzApi\Types;

/**
 * @version 2020-10-22
 * @author Tomas Palider (DJTommek) https://tomas.palider.cz/
 */
abstract class Type
{
	/**
	 * Cast stdClass into specific type
	 *
	 * @author https://stackoverflow.com/a/3243949/3334403
	 * @author https://tommcfarlin.com/cast-a-php-standard-class-to-a-specific-type/
	 */
	public static function cast(\stdClass $instance)
	{
		return unserialize(sprintf(
			'O:%d:"%s"%s',
			\strlen(static::class),
			static::class,
			strstr(strstr(serialize($instance), '"'), ':')
		));
	}

}
