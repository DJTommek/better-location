<?php declare(strict_types=1);

namespace GlympseApi\Types;

abstract class Type
{
	public function __set($name, $value) {
		throw new \OutOfBoundsException(sprintf('Property "%s$%s" is not predefined.', static::class, $name));
	}
}
