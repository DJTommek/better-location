<?php declare(strict_types=1);

namespace Tests;

use DJTommek\Coordinates\CoordinatesInterface;

trait LocationTrait
{
	public abstract static function assertSame($expected, $actual, string $message = ''): void;

	public abstract static function assertEqualsWithDelta($expected, $actual, float $delta, string $message = ''): void;

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
}
