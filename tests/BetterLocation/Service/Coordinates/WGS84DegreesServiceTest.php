<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreesService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreesService::getLink(50.087451, 14.420671, true);
	}

	public function testValidCoordinatesWithHemisphereAndDegreeSign1(): void
	{
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°N, 14.337469°E')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °N, 14.337469 °E')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° N, 14.337469° E')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° N, 14.337469 ° E')->getFirst()->__toString());

		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N°50.636144, E°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N° 50.636144, E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N °50.636144, E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N ° 50.636144, E ° 14.337469')->getFirst()->__toString());

		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N50.636144°, E°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N50.636144 °, E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N50.636144° , E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N50.636144 ° , E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N 50.636144°, E °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N 50.636144 °, E ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N 50.636144° , E ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('N 50.636144 ° , E ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithHemisphereAndDegreeSign2(): void
	{
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°S, 14.337469°E')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °S, 14.337469 °E')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° S, 14.337469° E')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° S, 14.337469 ° E')->getFirst()->__toString());

		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S°50.636144, E°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S° 50.636144, E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S °50.636144, E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S ° 50.636144, E ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S50.636144°, E°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S50.636144 °, E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S50.636144° , E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S50.636144 ° , E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S 50.636144°, E °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S 50.636144 °, E ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S 50.636144° , E ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('S 50.636144 ° , E ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithHemisphereAndDegreeSign3(): void
	{
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144°N, 14.337469°W')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 °N, 14.337469 °W')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144° N, 14.337469° W')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 ° N, 14.337469 ° W')->getFirst()->__toString());

		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N°50.636144, W°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N° 50.636144, W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N °50.636144, W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N ° 50.636144, W ° 14.337469')->getFirst()->__toString());

		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N50.636144°, W°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N50.636144 °, W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N50.636144° , W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N50.636144 ° , W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N 50.636144°, W °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N 50.636144 °, W ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N 50.636144° , W ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('N 50.636144 ° , W ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithHemisphereAndDegreeSign4(): void
	{
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144°S, 14.337469°W')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 °S, 14.337469 °W')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144° S, 14.337469° W')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 ° S, 14.337469 ° W')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S°50.636144, W°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S° 50.636144, W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S °50.636144, W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S ° 50.636144, W ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S50.636144°, W°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S50.636144 °, W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S50.636144° , W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S50.636144 ° , W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S 50.636144°, W °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S 50.636144 °, W ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S 50.636144° , W ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('S 50.636144 ° , W ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesCaseSensitivity(): void
	{
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°n, 14.337469°E')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °N, 14.337469 °e')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° n, 14.337469° e')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphereAndDegreeSign1(): void
	{
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°, 14.337469°')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °, 14.337469 °')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° , 14.337469° ')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° , 14.337469 ° ')->getFirst()->__toString());

		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('°50.636144, °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('° 50.636144,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' °50.636144,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' ° 50.636144,  ° 14.337469')->getFirst()->__toString());

		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°, °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' 50.636144°,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' 50.636144 °,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' 50.636144° ,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic(' 50.636144 ° ,  ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphereAndDegreeSign2(): void
	{
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144°, 14.337469°')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 °, 14.337469 °')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144° , 14.337469° ')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 ° , 14.337469 ° ')->getFirst()->__toString());

		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-°50.636144, °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-° 50.636144,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- °50.636144,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- ° 50.636144,  ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144°, °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 °,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 ° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144°,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144 °,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144° ,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144 ° ,  ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphereAndDegreeSign3(): void
	{
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144°, 14.337469°-')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 °, 14.337469 °-')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144° , 14.337469° -')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 ° , 14.337469 ° -')->getFirst()->__toString());

		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('°50.636144, -°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('° 50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' °50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' ° 50.636144, - ° 14.337469')->getFirst()->__toString());

		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144°, -°14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 °, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 ° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' 50.636144°, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' 50.636144 °, - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' 50.636144° , - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic(' 50.636144 ° , - ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphereAndDegreeSign4(): void
	{
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144°-, 14.337469°-')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 °-, 14.337469 °-')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144° -, 14.337469° -')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('50.636144 ° -, 14.337469 ° -')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-°50.636144, -°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-° 50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- °50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- ° 50.636144, - ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144°, -°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144 °, - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144 ° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144°, - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144 °, - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144° , - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144 ° , - ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphere(): void
	{
		// optional space and comma
		$this->assertSame('50.123456,10.123456', WGS84DegreesService::processStatic('50.123456 10.123456')->getFirst()->__toString());
		$this->assertSame('50.123456,10.123456', WGS84DegreesService::processStatic('50.123456, 10.123456')->getFirst()->__toString());
		$this->assertSame('50.123456,10.123456', WGS84DegreesService::processStatic('50.123456,10.123456')->getFirst()->__toString());
		// optional space and comma negative lat
		$this->assertSame('-50.123456,10.123456', WGS84DegreesService::processStatic('-50.123456 10.123456')->getFirst()->__toString());
		$this->assertSame('-50.123456,10.123456', WGS84DegreesService::processStatic('-50.123456, 10.123456')->getFirst()->__toString());
		$this->assertSame('-50.123456,10.123456', WGS84DegreesService::processStatic('-50.123456,10.123456')->getFirst()->__toString());
		// optional space and comma negative lon
		$this->assertSame('50.123456,-10.123456', WGS84DegreesService::processStatic('50.123456 -10.123456')->getFirst()->__toString());
		$this->assertSame('50.123456,-10.123456', WGS84DegreesService::processStatic('50.123456, -10.123456')->getFirst()->__toString());
		$this->assertSame('50.123456,-10.123456', WGS84DegreesService::processStatic('50.123456,-10.123456')->getFirst()->__toString());
		// optional space and comma negative both lat and lon
		$this->assertSame('-50.123456,-10.123456', WGS84DegreesService::processStatic('-50.123456 -10.123456')->getFirst()->__toString());
		$this->assertSame('-50.123456,-10.123456', WGS84DegreesService::processStatic('-50.123456, -10.123456')->getFirst()->__toString());
		$this->assertSame('-50.123456,-10.123456', WGS84DegreesService::processStatic('-50.123456,-10.123456')->getFirst()->__toString());
		// missing numbers after decimal point
		$this->assertSame('56.123400,16.123456', WGS84DegreesService::processStatic('56.1234 16.123456')->getFirst()->__toString());
		$this->assertSame('56.123456,16.123400', WGS84DegreesService::processStatic('56.123456 16.1234')->getFirst()->__toString());
		$this->assertSame('56.123400,16.123400', WGS84DegreesService::processStatic('56.1234 16.1234')->getFirst()->__toString());
		// various degree number size (all four combinations +/+, +/-, -/+, -/-)
		$this->assertSame('0.123400,0.123400', WGS84DegreesService::processStatic('0.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('0.123400,-0.123400', WGS84DegreesService::processStatic('0.1234 -0.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,0.123400', WGS84DegreesService::processStatic('-0.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,-0.123400', WGS84DegreesService::processStatic('-0.1234 -0.1234')->getFirst()->__toString());

		$this->assertSame('1.123400,0.123400', WGS84DegreesService::processStatic('1.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('1.123400,-0.123400', WGS84DegreesService::processStatic('1.1234 -0.1234')->getFirst()->__toString());
		$this->assertSame('-1.123400,0.123400', WGS84DegreesService::processStatic('-1.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('-1.123400,-0.123400', WGS84DegreesService::processStatic('-1.1234 -0.1234')->getFirst()->__toString());

		$this->assertSame('0.123400,2.123400', WGS84DegreesService::processStatic('0.1234 2.1234')->getFirst()->__toString());
		$this->assertSame('0.123400,-2.123400', WGS84DegreesService::processStatic('0.1234 -2.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,2.123400', WGS84DegreesService::processStatic('-0.1234 2.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,-2.123400', WGS84DegreesService::processStatic('-0.1234 -2.1234')->getFirst()->__toString());

		$this->assertSame('10.123400,0.123400', WGS84DegreesService::processStatic('10.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('10.123400,-0.123400', WGS84DegreesService::processStatic('10.1234 -0.1234')->getFirst()->__toString());
		$this->assertSame('-10.123400,0.123400', WGS84DegreesService::processStatic('-10.1234 0.1234')->getFirst()->__toString());
		$this->assertSame('-10.123400,-0.123400', WGS84DegreesService::processStatic('-10.1234 -0.1234')->getFirst()->__toString());

		$this->assertSame('0.123400,10.123400', WGS84DegreesService::processStatic('0.1234 10.1234')->getFirst()->__toString());
		$this->assertSame('0.123400,-10.123400', WGS84DegreesService::processStatic('0.1234 -10.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,10.123400', WGS84DegreesService::processStatic('-0.1234 10.1234')->getFirst()->__toString());
		$this->assertSame('-0.123400,-10.123400', WGS84DegreesService::processStatic('-0.1234 -10.1234')->getFirst()->__toString());

		$this->assertSame('10.123400,10.123400', WGS84DegreesService::processStatic('10.1234 10.1234')->getFirst()->__toString());
		$this->assertSame('10.123400,-10.123400', WGS84DegreesService::processStatic('10.1234 -10.1234')->getFirst()->__toString());
		$this->assertSame('-10.123400,10.123400', WGS84DegreesService::processStatic('-10.1234 10.1234')->getFirst()->__toString());
		$this->assertSame('-10.123400,-10.123400', WGS84DegreesService::processStatic('-10.1234 -10.1234')->getFirst()->__toString());

		$this->assertSame('89.999999,99.123400', WGS84DegreesService::processStatic('89.999999 99.1234')->getFirst()->__toString());
		$this->assertSame('89.999999,-99.123400', WGS84DegreesService::processStatic('89.999999 -99.1234')->getFirst()->__toString());
		$this->assertSame('-89.999999,99.123400', WGS84DegreesService::processStatic('-89.999999 99.1234')->getFirst()->__toString());
		$this->assertSame('-89.999999,-99.123400', WGS84DegreesService::processStatic('-89.999999 -99.1234')->getFirst()->__toString());

		$this->assertSame('90.000000,99.123400', WGS84DegreesService::processStatic('90.000000 99.1234')->getFirst()->__toString());
		$this->assertSame('90.000000,-99.123400', WGS84DegreesService::processStatic('90.000000 -99.1234')->getFirst()->__toString());
		$this->assertSame('-90.000000,99.123400', WGS84DegreesService::processStatic('-90.000000 99.1234')->getFirst()->__toString());
		$this->assertSame('-90.000000,-99.123400', WGS84DegreesService::processStatic('-90.000000 -99.1234')->getFirst()->__toString());

		$this->assertSame('89.999999,100.123400', WGS84DegreesService::processStatic('89.999999 100.1234')->getFirst()->__toString());
		$this->assertSame('89.999999,-100.123400', WGS84DegreesService::processStatic('89.999999 -100.1234')->getFirst()->__toString());
		$this->assertSame('-89.999999,100.123400', WGS84DegreesService::processStatic('-89.999999 100.1234')->getFirst()->__toString());
		$this->assertSame('-89.999999,-100.123400', WGS84DegreesService::processStatic('-89.999999 -100.1234')->getFirst()->__toString());

		$this->assertSame('89.999999,179.999999', WGS84DegreesService::processStatic('89.999999 179.999999')->getFirst()->__toString());
		$this->assertSame('89.999999,-179.999999', WGS84DegreesService::processStatic('89.999999 -179.999999')->getFirst()->__toString());
		$this->assertSame('-89.999999,179.999999', WGS84DegreesService::processStatic('-89.999999 179.999999')->getFirst()->__toString());
		$this->assertSame('-89.999999,-179.999999', WGS84DegreesService::processStatic('-89.999999 -179.999999')->getFirst()->__toString());

		$this->assertSame('89.999999,180.000000', WGS84DegreesService::processStatic('89.999999 180.0')->getFirst()->__toString());
		$this->assertSame('89.999999,-180.000000', WGS84DegreesService::processStatic('89.999999 -180.0')->getFirst()->__toString());
		$this->assertSame('-89.999999,180.000000', WGS84DegreesService::processStatic('-89.999999 180.0')->getFirst()->__toString());
		$this->assertSame('-89.999999,-180.000000', WGS84DegreesService::processStatic('-89.999999 -180.0')->getFirst()->__toString());

		$this->assertSame('90.000000,180.000000', WGS84DegreesService::processStatic('90.0 180.0')->getFirst()->__toString());
		$this->assertSame('90.000000,-180.000000', WGS84DegreesService::processStatic('90.0 -180.0')->getFirst()->__toString());
		$this->assertSame('-90.000000,180.000000', WGS84DegreesService::processStatic('-90.0 180.0')->getFirst()->__toString());
		$this->assertSame('-90.000000,-180.000000', WGS84DegreesService::processStatic('-90.0 -180.0')->getFirst()->__toString());
	}

	public function testNothingInText(): void
	{
		$this->assertSame([], WGS84DegreesService::findInText('Nothing valid')->getLocations());
	}

	public function testCoordinatesInText(): void
	{
		$text = PHP_EOL;
		$text .= '50.1111 10.2222' . PHP_EOL;       // +/+
		$text .= '-51.1111 -11.2222' . PHP_EOL;     // -/-
		$text .= PHP_EOL;
		$text .= 'N52.1111 E12.2222' . PHP_EOL;     // +/+
		$text .= 'S53.1111 W13.2222' . PHP_EOL;     // -/-
		$text .= PHP_EOL;
		$text .= '54.1111N 14.2222E' . PHP_EOL;     // +/+
		$text .= '55.1111S 15.2222W' . PHP_EOL;     // -/-
		$text .= PHP_EOL;
		$text .= '16.2222E 56.1111N' . PHP_EOL;     // +/+
		$text .= '17.2222W 57.1111S' . PHP_EOL;     // -/-
		$text .= PHP_EOL;
		$text .= '18.2222E 58.1111S' . PHP_EOL;     // -/+
		$text .= '19.2222W 59.1111N' . PHP_EOL;     // +/-
		$text .= PHP_EOL;
		$text .= 'Invalid:';
		$text .= '20.2222S 60.1111S' . PHP_EOL;     // Both coordinates are north-south hemisphere
		$text .= '21.2222W 61.1111E' . PHP_EOL;     // Both coordinates are east-west hemisphere

		$betterLocations = WGS84DegreesService::findInText($text);
		$this->assertCount(10, $betterLocations);
		$this->assertSame([50.1111, 10.2222], $betterLocations[0]->getLatLon());
		$this->assertSame([-51.1111, -11.2222], $betterLocations[1]->getLatLon());
		$this->assertSame([52.1111, 12.2222], $betterLocations[2]->getLatLon());
		$this->assertSame([-53.1111, -13.2222], $betterLocations[3]->getLatLon());
		$this->assertSame([54.1111, 14.2222], $betterLocations[4]->getLatLon());
		$this->assertSame([-55.1111, -15.2222], $betterLocations[5]->getLatLon());
		$this->assertSame([56.1111, 16.2222], $betterLocations[6]->getLatLon());
		$this->assertSame([-57.1111, -17.2222], $betterLocations[7]->getLatLon());
		$this->assertSame([-58.1111, 18.2222], $betterLocations[8]->getLatLon());
		$this->assertSame([59.1111, -19.2222], $betterLocations[9]->getLatLon());
	}

	public function testDynamicHemispherePositionFirst(): void
	{
		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, 13.5560753E'); // text from cipher solutions on i-quest.cz
		$this->assertCount(1, $collection);
		$this->assertSame('49.143818,13.556075', $collection->getFirst()->key());


		$text = 'GPS: 49.1438181N, 13.5560753E' . PHP_EOL;
		$text .= 'GPS: 49.1266744N, 13.6268717E' . PHP_EOL;
		$text .= 'GPS: 49.1208042N, 13.6637094E';

		$collection = WGS84DegreesService::findInText($text); // text from cipher solutions on i-quest.cz
		$this->assertCount(3, $collection);
		$this->assertSame('49.143818,13.556075', $collection[0]->key());
		$this->assertSame('49.126674,13.626872', $collection[1]->key());
		$this->assertSame('49.120804,13.663709', $collection[2]->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181, 13.5560753E');
		$this->assertCount(1, $collection);
		$this->assertSame('-49.143818,13.556075', $collection->getFirst()->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181S, 13.5560753E');
		$this->assertCount(1, $collection);
		$this->assertSame('-49.143818,13.556075', $collection->getFirst()->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, E13.5560753');
		$this->assertCount(1, $collection);
		$this->assertSame('-49.143818,13.556075', $collection->getFirst()->key());

		// @TODO this should return valid location, but it is hard to detect, what are coordinates and what is normal text
		// because text before ends with letter 'S' and text after starts with 'W', both are hemispere characters.
		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, E13.5560753 Wasted, it suck it here!');
		$this->assertCount(0, $collection);
	}

	public function testDynamicHemispherePositionSecond(): void
	{
		// 'N' character in 'No random' is also detected
		$collection = WGS84DegreesService::findInText('W49.1438181, S13.5560753 No random text');
		$this->assertCount(1, $collection);
		$this->assertSame('-13.556075,-49.143818', $collection->getFirst()->key());

		// 'N' character in 'No random' is also detected
		$collection = WGS84DegreesService::findInText('E49.1438181, S13.5560753 No random text');
		$this->assertCount(1, $collection);
		$this->assertSame('-13.556075,49.143818', $collection->getFirst()->key());

		// Latitude hemisphere is defined twice
		$collection = WGS84DegreesService::findInText('S49.1438181, S13.5560753 No random text');
		$this->assertCount(0, $collection);
	}
}
