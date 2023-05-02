<?php declare(strict_types=1);

namespace Tests;

trait IteratorTrait
{
	/**
	 * Hacky way how to run test multiple times
	 */
	private static function iteratorProvider(int $count): \Generator
	{
		if ($count <= 0) {
			throw new \InvalidArgumentException('Value must be higher than 0');
		}

		for ($i = 0; $i < $count; $i++) {
			yield ['#' . $i => []];
		}
	}

	/**
	 * Hacky way how to run test multiple times (in this case 100x times)
	 */
	public static function iterator100Provider(): \Generator
	{
		yield from self::iteratorProvider(100);
	}
}
