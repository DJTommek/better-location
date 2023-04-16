<?php declare(strict_types=1);

namespace App\Foursquare\Types;

use App\Utils\DateImmutableUtils;
use Tracy\Debugger;

abstract class Type
{
	private final function __construct()
	{
	}

	public static function createFromVariable(\stdClass $variables): static
	{
		$class = new static();
		foreach ((array)$variables as $key => $value) {
			if ($key === 'createdAt') {
				$value = DateImmutableUtils::fromTimestamp($value);
			} else if ($key === 'location') {
				$value = VenueLocationType::createFromVariable($value);
			}
			$class->{$key} = $value;
		}
		return $class;
	}

	public function __set(string $name, mixed $value): void
	{
		Debugger::log(sprintf('Property "%s$%s" of type "%s" is not predefined.', static::class, $name, gettype($value)), Debugger::WARNING);
	}
}
