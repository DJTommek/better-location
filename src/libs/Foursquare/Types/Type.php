<?php declare(strict_types=1);

namespace App\Foursquare\Types;

use App\Utils\DateImmutableUtils;
use Tracy\Debugger;

abstract class Type
{
	public static function createFromVariable(\stdClass $variables)
	{
		$class = new static();
		foreach ($variables as $key => $value) {
			if ($key === 'createdAt') {
				$value = DateImmutableUtils::fromTimestamp($value);
			} else if ($key === 'location') {
				$value = VenueLocationType::createFromVariable($value);
			}
			$class->{$key} = $value;
		}
		return $class;
	}

	/** @param mixed $value */
	public function __set(string $name, $value): void
	{
		Debugger::log(sprintf('Property "%s$%s" is not predefined.', static::class, $name), Debugger::WARNING);
	}
}
