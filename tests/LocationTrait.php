<?php declare(strict_types=1);

namespace Tests;

use App\BetterLocation\BetterLocationCollection;
use DJTommek\Coordinates\CoordinatesInterface;

trait LocationTrait
{
	public abstract static function assertSame($expected, $actual, string $message = ''): void;

	public abstract static function assertEqualsWithDelta($expected, $actual, float $delta, string $message = ''): void;

	public abstract static function assertCount(int $expectedCount, \Countable|\Traversable|array $haystack, string $message = ''): void;

	protected function assertCoords(float $expectedLat, float $expectedLon, CoordinatesInterface $location): void
	{
		$this->assertSame($expectedLat, $location->getLat());
		$this->assertSame($expectedLon, $location->getLon());
	}

	protected function assertCoordsWithDelta(float $expectedLat, float $expectedLon, CoordinatesInterface $location, float $delta = 0.000_001): void
	{
		$this->assertEqualsWithDelta($expectedLat, $location->getLat(), $delta);
		$this->assertEqualsWithDelta($expectedLon, $location->getLon(), $delta);
	}

	protected function assertOneInCollection(float $expectedLat, float $expectedLon, ?string $sourceType, BetterLocationCollection $collection): void
	{
		$this->assertCount(1, $collection);
		$location = $collection->getFirst();
		$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location);
		$this->assertSame($sourceType, $location->getSourceType());
	}

	/**
	 * @param list<list{float, float, ?string, ?string}> $expectedResults
	 *      latitude
	 *      longitude
	 *      sourceType      Default is null
	 *      prefixMessage   Not asserting if null or not provided
	 */
	protected function assertCollection(
		BetterLocationCollection $collection,
		array $expectedResults,
		float $delta = 0.000_001,
	): void
	{
		$this->assertCount(count($expectedResults), $collection);

		foreach ($expectedResults as $key => $expectedResult) {
			$expectedLat = $expectedResult[0];
			$expectedLon = $expectedResult[1];
			$location = $collection[$key];
			$this->assertCoordsWithDelta($expectedLat, $expectedLon, $location, $delta);
			$expectedSourceType = $expectedResult[2] ?? null;
			$this->assertSame($expectedSourceType, $location->getSourceType());

			$expectedPrefix = $expectedResult[3] ?? null;
			if ($expectedPrefix !== null) {
				$this->assertSame($expectedPrefix, $location->getPrefixMessage());
			}
		}
	}
}
