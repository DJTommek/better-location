<?php declare(strict_types=1);

namespace App\Factory;

trait ObjectsFilterTrait
{
	/**
	 * Iterate via objects and return only these, that are instances of given instance.
	 *
	 * @param iterable<object> $input
	 * @param class-string $instance Return
	 * @return iterable<class-string, object>
	 */
	private function filter(iterable $input, string $instance, bool $runAssert): iterable
	{
		foreach ($input as $inputObject) {
			if ($runAssert) {
				assert(
					$inputObject instanceof $instance,
					sprintf('%s must contain only instances of %s but %s provided.', static::class, $instance, $inputObject::class)
				);
			}

			if ($inputObject instanceof $instance) {
				yield $inputObject::class => $inputObject;
			}
		}
	}
}
