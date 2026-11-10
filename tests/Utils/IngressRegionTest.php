<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\IngressRegion;
use App\Utils\S2Cell;
use PHPUnit\Framework\TestCase;

final class IngressRegionTest extends TestCase
{
	public static function fromCoordinatesProvider(): array
	{
		return [
			'Prague' => ['NR02-FOXTROT-05', 50.087451, 14.420671],
			'Pilsen' => ['NR02-FOXTROT-05', 49.7477, 13.3776],
			'Příbram' => ['NR02-FOXTROT-05', 49.6899, 14.0104],
			'Chomutov' => ['NR02-FOXTROT-05', 50.4605, 13.4178],
			'Brno' => ['NR02-FOXTROT-09', 49.1951, 16.6068],
			'New York' => ['AM01-LIMA-01', 40.7128, -74.006],
			'Sydney' => ['PA03-ROMEO-09', -33.8688, 151.2093],
			'Tokyo' => ['PA01-ALPHA-12', 35.6762, 139.6503],
			'Fiji (antimeridian)' => ['PA08-MIKE-15', -17.7, 179.99],
			'Near north pole' => ['NR08-HOTEL-10', 89.99, 10],
			'Near south pole' => ['ST10-JULIET-03', -80.474066,27.147216],
			'Example coords 3' => ['ST15-LIMA-05', -50.2, 14.6000001],
			'Example coords 4' => ['NR02-LIMA-10', 50.3, -14.7000009],
			'Example coords 5' => ['ST15-FOXTROT-10', -50.4, -14.800008],
			'AM15-ROMEO-12 - 1' => ['AM15-ROMEO-12', -29.976519, -51.178858],
			'AM15-ROMEO-12 - 2' => ['AM15-ROMEO-12',-29.541922,-51.081713],
			'AM15-ROMEO-12 - 3' => ['AM15-ROMEO-12',-29.964823,-50.123781],
			'AM15-ROMEO-12 - 4' => ['AM15-ROMEO-12',-30.233793,-50.261426],
			'AM15-ROMEO-12 - 5' => ['AM15-ROMEO-12',-30.408293,-50.502487],
		];
	}

	/**
	 * @dataProvider fromCoordinatesProvider
	 */
	public function testFromCoordinates(string $expectedName, float $lat, float $lon): void
	{
		$this->assertSame($expectedName, IngressRegion::fromCoordinates($lat, $lon));
	}

	public static function nameToCellProvider(): array
	{
		return [
			['NR02-FOXTROT-05', 49.859109764, 13.761333119, 'NR02-FOXTROT-05'],
			['NR02-FOXTROT-05', 49.859109764, 13.761333119, 'nr02-foxtrot-05'],
			['NR02-FOXTROT-05', 49.859109764, 13.761333119, 'NR2FOXTROT5'],
			['NR02-FOXTROT-05', 49.859109764, 13.761333119, ' NR 02 FOXTROT 05 '],
			['AM01-LIMA-01', 40.827706513, -74.217932561, 'AM01-LIMA-01'],
			['PA08-MIKE-15', -17.289387991, 179.398528316, 'PA08-MIKE-15'],
			['NR08-HOTEL-10', 89.149421831, 45.0, 'NR08-HOTEL-10'],
			['AF01-ALPHA-00', -34.978731431, -44.399289156, 'AF01-ALPHA-00'],
			['ST16-SIERRA-15', -37.524797049, 41.229382348, 'ST16-SIERRA-15'],
		];
	}

	/**
	 * @dataProvider nameToCellProvider
	 */
	public function testNameToCell(string $expectedName, float $expectedLat, float $expectedLon, string $input): void
	{
		$cell = IngressRegion::nameToCell($input);
		$this->assertNotNull($cell);
		$this->assertSame(IngressRegion::LEVEL, $cell->level);
		$this->assertSame($expectedName, IngressRegion::cellToName($cell));
		$this->assertSame($expectedName, IngressRegion::normalizeName($input));

		$center = $cell->getCenter();
		$this->assertEqualsWithDelta($expectedLat, $center->getLat(), 0.000_001);
		$this->assertEqualsWithDelta($expectedLon, $center->getLon(), 0.000_001);
	}

	public static function invalidNameProvider(): array
	{
		return [
			[''],
			['NR02-FOXTROT'], // cluster of 16 regions, not single region
			['FOXTROT-05'],
			['NR00-FOXTROT-05'], // number must be 1-16
			['NR17-FOXTROT-05'],
			['NR02-FOXTROT-16'], // cell number must be 0-15
			['NR02-INDIA-05'], // not a code word
			['XX02-FOXTROT-05'],
			['NR02-FOXTROT-05-01'],
		];
	}

	/**
	 * @dataProvider invalidNameProvider
	 */
	public function testInvalidName(string $input): void
	{
		$this->assertFalse(IngressRegion::isValid($input));
		$this->assertNull(IngressRegion::nameToCell($input));
		$this->assertNull(IngressRegion::normalizeName($input));
	}

	/**
	 * Every level 6 cell on all faces must have unique name, which converts back to the same cell, and center of
	 * every cell must be inside that cell.
	 */
	public function testAllCellsRoundTrip(): void
	{
		$names = [];
		$size = 1 << IngressRegion::LEVEL;
		for ($face = 0; $face < 6; $face++) {
			for ($i = 0; $i < $size; $i++) {
				for ($j = 0; $j < $size; $j++) {
					$cell = S2Cell::fromFaceIJ($face, $i, $j, IngressRegion::LEVEL);
					$name = IngressRegion::cellToName($cell);
					$names[$name] = true;

					$fromName = IngressRegion::nameToCell($name);
					$this->assertNotNull($fromName);
					$this->assertTrue($cell->equals($fromName), $name);

					$center = $cell->getCenter();
					$this->assertTrue($cell->equals(S2Cell::fromLatLon($center->getLat(), $center->getLon(), IngressRegion::LEVEL)), $name);
				}
			}
		}
		$this->assertCount(6 * $size * $size, $names);
	}

	public function testArea(): void
	{
		$cell = IngressRegion::nameToCell('NR02-FOXTROT-05');
		$this->assertNotNull($cell);
		$this->assertEqualsWithDelta(18_520, $cell->getAreaM2() / 1_000_000, 1);

		// Sum of areas of all level 0 cells (faces) must be the area of whole Earth
		$total = 0;
		for ($face = 0; $face < 6; $face++) {
			$total += S2Cell::fromFaceIJ($face, 0, 0, 0)->getAreaM2();
		}
		$this->assertEqualsWithDelta(4 * M_PI * \App\Utils\Coordinates::EARTH_RADIUS ** 2, $total, 1);
	}
}
