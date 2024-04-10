<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\Utils\Coordinates;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;
use Tests\BetterLocation\Service\AbstractServiceTestCase;

final class WGS84DegreesServiceTest extends AbstractServiceTestCase
{

	protected function getServiceClass(): string
	{
		return WGS84DegreesService::class;
	}

	protected function getShareLinks(): array
	{
		return [];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public function testIsValid(): void
	{
		$this->assertTrue(WGS84DegreesService::isValidStatic('50.636144°N, 14.337469°E'));
	}

	public function testProcess(): void
	{
		$this->assertLocation('50.636144°N, 14.337469°E', 50.636144, 14.337469);
	}

	/**
	 * @dataProvider dataProviderValidCoordinatesWithHemisphere
	 */
	public function testProcess2(CoordinatesInterface $expectedCoords, string $input): void
	{
		$this->assertLocation($input, $expectedCoords->getLat(), $expectedCoords->getLon());
	}

	/**
	 * Generate coordinates in various format containing hemisphere as letter and degree sign
	 * - all combination of N, S, E and W
	 * - uppercased and lowercased
	 * - multiple coordinates
	 */
	public function dataProviderValidCoordinatesWithHemisphere(): \Generator
	{
		$coordinatesRaw = [
			[50.636144, 14.337469],
			[1.123400, 0.123400],
			[89.9999, 179.9999],
		];

		$formatStrings = [
			// lat hemisphere lon hemisphere
			'%1$s°%2$s, %3$s°%4$s',
			'%1$s °%2$s, %3$s °%4$s',
			'%1$s° %2$s, %3$s° %4$s',
			'%1$s ° %2$s, %3$s ° %4$s',

			// hemisphere lat hemisphere lon
			'%2$s°%1$s, %4$s°%3$s',
			'%2$s° %1$s, %4$s °%3$s',
			'%2$s °%1$s, %4$s °%3$s',
			'%2$s ° %1$s, %4$s ° %3$s',

			// hemisphere lat lon hemisphere
			'%2$s%1$s°, %4$s°%3$s',
			'%2$s%1$s °, %4$s °%3$s',
			'%2$s%1$s° , %4$s °%3$s',
			'%2$s%1$s ° , %4$s °%3$s',
			'%2$s %1$s°, %4$s °%3$s',
			'%2$s %1$s °, %4$s ° %3$s',
			'%2$s %1$s° , %4$s ° %3$s',
			'%2$s %1$s ° , %4$s ° %3$s',
		];

		foreach ($coordinatesRaw as [$lat, $lon]) {
			foreach (Coordinates::HEMISPHERES_NS as $flipNS => $hemisphereNS) {
				foreach (Coordinates::HEMISPHERES_EW as $flipEW => $hemisphereEW) {
					$expected = new CoordinatesImmutable($lat * $flipNS, $lon * $flipEW);
					foreach ($formatStrings as $string) {
						$input = sprintf($string, $lat, $hemisphereNS, $lon, $hemisphereEW);
						yield [$expected, strtolower($input)];
						yield [$expected, strtoupper($input)];
					}
				}
			}
		}
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
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- °50.636144,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- ° 50.636144,  ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144°, °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 °,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,14.337469', WGS84DegreesService::processStatic('-50.636144 ° ,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144°,  °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144 °,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144° ,  ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('- 50.636144 ° ,  ° 14.337469')->getFirst()->__toString());
	}

	public function testValidCoordinatesWithoutHemisphereAndDegreeSign3(): void
	{
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°, 14.337469°-')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °, 14.337469 °-')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° , 14.337469° -')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° , 14.337469 ° -')->getFirst()->__toString());

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
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144°-, 14.337469°-')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 °-, 14.337469 °-')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144° -, 14.337469° -')->getFirst()->__toString());
		$this->assertSame('50.636144,14.337469', WGS84DegreesService::processStatic('50.636144 ° -, 14.337469 ° -')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-°50.636144, -°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-° 50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- °50.636144, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- ° 50.636144, - ° 14.337469')->getFirst()->__toString());

		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144°, -°14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144 °, - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('-50.636144,-14.337469', WGS84DegreesService::processStatic('-50.636144 ° , - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144°, - °14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144 °, - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144° , - ° 14.337469')->getFirst()->__toString());
		$this->assertSame('50.636144,-14.337469', WGS84DegreesService::processStatic('- 50.636144 ° , - ° 14.337469')->getFirst()->__toString());
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

		$text = <<<TEXT
Title - Coords - Note
Title1 - 50.087451,14.420671 - some note
Title2 - 50.087451,13.420671 - another note
Title3 - -50.087451,13.420671 - negative lat
Title3 - 50.087451,-13.420671 - negative lon
Title3 - -50.087451,-13.420671 - negative lat lon
TEXT;

		$collection = WGS84DegreesService::findInText($text);
		$this->assertCount(5, $collection);
		$this->assertSame([50.087451, 14.420671], $collection[0]->getLatLon());
		$this->assertSame([50.087451, 13.420671], $collection[1]->getLatLon());
		$this->assertSame([-50.087451, 13.420671], $collection[2]->getLatLon());
		$this->assertSame([50.087451, -13.420671], $collection[3]->getLatLon());
		$this->assertSame([-50.087451, -13.420671], $collection[4]->getLatLon());

	}

	public function testDynamicHemispherePositionFirst(): void
	{
		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, 13.5560753E'); // text from cipher solutions on i-quest.cz
		$this->assertCount(1, $collection);
		$this->assertSame('49.143818,13.556075', $collection->getFirst()->key());

		// text from cipher solutions on i-quest.cz
		$text = 'GPS: 49.1438181N, 13.5560753E' . PHP_EOL;
		$text .= 'GPS: 49.1266744N, 13.6268717E' . PHP_EOL;
		$text .= 'GPS: 49.1208042N, 13.6637094E';

		$collection = WGS84DegreesService::findInText($text);
		$this->assertCount(3, $collection);
		$this->assertSame('49.143818,13.556075', $collection[0]->key());
		$this->assertSame('49.126674,13.626872', $collection[1]->key());
		$this->assertSame('49.120804,13.663709', $collection[2]->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181, 13.5560753E');
		$this->assertCount(1, $collection);
		// @TODO this is detecting character 'S' from word GPS which marks as south hemisphere
		// $this->assertSame('49.143818,13.556075', $collection->getFirst()->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181S, 13.5560753E');
		$this->assertCount(1, $collection);
		$this->assertSame('-49.143818,13.556075', $collection->getFirst()->key());

		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, E13.5560753');
		// @TODO this is detecting character 'S' from word GPS which marks as south hemisphere
		$this->assertCount(1, $collection);
		// $this->assertSame('49.143818,13.556075', $collection->getFirst()->key());

		// @TODO this should return valid location, but it is hard to detect, what are coordinates and what is normal text
		// because text before ends with letter 'S' and text after starts with 'W', both are hemispere characters.
		$collection = WGS84DegreesService::findInText('GPS: 49.1438181N, E13.5560753 Wasted, it suck it here!');
		// $this->assertCount(1, $collection);
		// $this->assertSame('49.143818,13.556075', $collection->getFirst()->key());
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
