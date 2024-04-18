<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;
use Tests\IteratorTrait;
use Tests\TestUtils;

final class CoordinatesTest extends TestCase
{
	use IteratorTrait;

	/**
	 * Assert two floats and treat them as distance. Calculation might differ based on float precision set by PHP so
	 * allow some small delta differences between them.
	 */
	private function assertDistance(float $expected, float $real): void
	{
		$this->assertEqualsWithDelta(
			$expected,
			$real,
			0.000_000_01
		);
	}

	public function testFromString(): void
	{
		$this->assertSame('49.885617,14.044381', Coordinates::fromString('49.885617,14.044381')->key());
		$this->assertSame('-49.885617,14.044381', Coordinates::fromString('-49.885617,14.044381')->key());
		$this->assertSame('49.885617,-14.044381', Coordinates::fromString('49.885617,-14.044381')->key());
		$this->assertSame('-49.885617,-14.044381', Coordinates::fromString('-49.885617,-14.044381')->key());
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567,0.123456')->key());
		$this->assertSame('0.000000,0.000000', Coordinates::fromString('0,0')->key());

		// different separator
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567_0.123456', '_')->key());
		$this->assertNull(Coordinates::fromString('1.234567,0.123456', '_'));

		// multi-character separator separator
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567___0.123456', '___')->key());
		$this->assertNull(Coordinates::fromString('1.234567__0.123456', '___'));
		$this->assertSame('1.234567,0.123456', Coordinates::fromString('1.234567_abcd_0.123456', '_abcd_')->key());
		$this->assertNull(Coordinates::fromString('1.234567__0.123456', '___'));
		$this->assertNull(Coordinates::fromString('1.234567___0.123456', '_'));

		$this->assertNull(Coordinates::fromString('some random text'));
		$this->assertNull(Coordinates::fromString('valid coords (49.885617,14.044381) but inside text'));
		$this->assertNull(Coordinates::fromString('95.885617,14.044381')); // lat out of bounds
		$this->assertNull(Coordinates::fromString('1.885617,180.044381')); // lon out of bounds
	}

	public function testWgs84DegreesToDegreesMinutes(): void
	{
		$this->assertSame([50, 5.24706000000009], Coordinates::wgs84DegreesToDegreesMinutes(50.087451));
		$this->assertSame([14, 25.240260000000028], Coordinates::wgs84DegreesToDegreesMinutes(14.420671));
		$this->assertSame([-41, 19.615200000000073], Coordinates::wgs84DegreesToDegreesMinutes(-41.326920));
		$this->assertSame([174, 48.46218000000022], Coordinates::wgs84DegreesToDegreesMinutes(174.807703));
		$this->assertSame([1, 0.0], Coordinates::wgs84DegreesToDegreesMinutes(1));
	}

	public function testWgs84DegreesToDegreesMinutesSeconds(): void
	{
		$this->assertSame([50, 5, 14.8236000000054], Coordinates::wgs84DegreesToDegreesMinutesSeconds(50.087451));
		$this->assertSame([14, 25, 14.41560000000166], Coordinates::wgs84DegreesToDegreesMinutesSeconds(14.420671));
		$this->assertSame([-41, 19, 36.912000000004355], Coordinates::wgs84DegreesToDegreesMinutesSeconds(-41.326920));
		$this->assertSame([174, 48, 27.730800000013005], Coordinates::wgs84DegreesToDegreesMinutesSeconds(174.807703));
		$this->assertSame([1, 0, 0.0], Coordinates::wgs84DegreesToDegreesMinutesSeconds(1));
	}

	public function testDistance(): void
	{
		$this->assertDistance(0.0, (new Coordinates(50.087725, 14.4211267))->distance(new Coordinates(50.087725, 14.4211267)));
		$this->assertDistance(42.16747601866312, (new Coordinates(50.087725, 14.4211267))->distance(new Coordinates(50.0873667, 14.4213203)));
		$this->assertDistance(1_825.0239867033586, (new Coordinates(36.6323425, -121.9340617))->distance(new Coordinates(36.6219297, -121.9182533)));

		$coord1 = new Coordinates(50, 14);
		$coord2 = new Coordinates(51, 15);

		$this->assertDistance( // same coordinates, just switched
			$coord1->distance($coord2),
			$coord2->distance($coord1)
		);
		$this->assertDistance(4_532.050463078125, (new Coordinates(50.08904, 14.42890))->distance(new Coordinates(50.07406, 14.48797)));
		$this->assertDistance(11_471_646.428581407, (new Coordinates(-50.08904, 14.42890))->distance(new Coordinates(50.07406, -14.48797)));
	}

	/**
	 * Generate random coordinates and compare distance between by using first and second set of method argument.
	 * @deprecated use {@see \DJTommek\Coordinates\Coordinates}
	 * @dataProvider iterator100Provider
	 */
	public function testDistanceGenerated(): void
	{
		$coords1 = new Coordinates(
			TestUtils::randomLat(),
			TestUtils::randomLon(),
		);
		$coords2 = new Coordinates(
			TestUtils::randomLat(),
			TestUtils::randomLon(),
		);

		$this->assertDistance(
			$coords1->distance($coords2),
			$coords2->distance($coords1)
		);
	}

	public function testDistanceStatic(): void
	{
		$this->assertDistance(0.0, Coordinates::distanceLatLon(50.087725, 14.4211267, 50.087725, 14.4211267));
		$this->assertDistance(42.16747601866312, Coordinates::distanceLatLon(50.087725, 14.4211267, 50.0873667, 14.4213203));
		$this->assertDistance(1_825.0239867033586, Coordinates::distanceLatLon(36.6323425, -121.9340617, 36.6219297, -121.9182533));

		$this->assertDistance( // same coordinates, just switched
			Coordinates::distanceLatLon(50, 14, 51, 15),
			Coordinates::distanceLatLon(51, 15, 50, 14)
		);
		$this->assertDistance(4_532.050463078125, Coordinates::distanceLatLon(50.08904, 14.42890, 50.07406, 14.48797));
		$this->assertDistance(11_471_646.428581407, Coordinates::distanceLatLon(-50.08904, 14.42890, 50.07406, -14.48797));
	}

	/**
	 * Generate random coordinates and compare distance between by using first and second set of method argument.
	 * @deprecated use {@see \DJTommek\Coordinates\Coordinates}
	 * @dataProvider iterator100Provider
	 */
	public function testDistanceStaticGenerated(): void
	{
		$lat1 = TestUtils::randomLat();
		$lon1 = TestUtils::randomLon();

		$lat2 = TestUtils::randomLat();
		$lon2 = TestUtils::randomLon();

		$this->assertDistance(
			Coordinates::distanceLatLon($lat1, $lon1, $lat2, $lon2),
			Coordinates::distanceLatLon($lat2, $lon2, $lat1, $lon1)
		);
	}
}
