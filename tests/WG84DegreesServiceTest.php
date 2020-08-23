<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\Coordinates\WG84DegreesService;
use \BetterLocation\Service\Exceptions\InvalidLocationException;

require_once __DIR__ . '/../src/config.php';

final class WG84DegreesServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testValidCoordinatesWithoutHemisphere(): void {
		// optional space and comma
		$this->assertEquals('50.123456, 10.123456', WG84DegreesService::parseCoords('50.123456 10.123456')->__toString());
		$this->assertEquals('50.123456, 10.123456', WG84DegreesService::parseCoords('50.123456, 10.123456')->__toString());
		$this->assertEquals('50.123456, 10.123456', WG84DegreesService::parseCoords('50.123456,10.123456')->__toString());
		// optional space and comma negative lat
		$this->assertEquals('-50.123456, 10.123456', WG84DegreesService::parseCoords('-50.123456 10.123456')->__toString());
		$this->assertEquals('-50.123456, 10.123456', WG84DegreesService::parseCoords('-50.123456, 10.123456')->__toString());
		$this->assertEquals('-50.123456, 10.123456', WG84DegreesService::parseCoords('-50.123456,10.123456')->__toString());
		// optional space and comma negative lon
		$this->assertEquals('50.123456, -10.123456', WG84DegreesService::parseCoords('50.123456 -10.123456')->__toString());
		$this->assertEquals('50.123456, -10.123456', WG84DegreesService::parseCoords('50.123456, -10.123456')->__toString());
		$this->assertEquals('50.123456, -10.123456', WG84DegreesService::parseCoords('50.123456,-10.123456')->__toString());
		// optional space and comma negative both lat and lon
		$this->assertEquals('-50.123456, -10.123456', WG84DegreesService::parseCoords('-50.123456 -10.123456')->__toString());
		$this->assertEquals('-50.123456, -10.123456', WG84DegreesService::parseCoords('-50.123456, -10.123456')->__toString());
		$this->assertEquals('-50.123456, -10.123456', WG84DegreesService::parseCoords('-50.123456,-10.123456')->__toString());
		// missing numbers after decimal point
		$this->assertEquals('56.123400, 16.123456', WG84DegreesService::parseCoords('56.1234 16.123456')->__toString());
		$this->assertEquals('56.123456, 16.123400', WG84DegreesService::parseCoords('56.123456 16.1234')->__toString());
		$this->assertEquals('56.123400, 16.123400', WG84DegreesService::parseCoords('56.1234 16.1234')->__toString());
		// various degree number size (all four combinations +/+, +/-, -/+, -/-)
		$this->assertEquals('0.123400, 0.123400', WG84DegreesService::parseCoords('0.1234 0.1234')->__toString());
		$this->assertEquals('0.123400, -0.123400', WG84DegreesService::parseCoords('0.1234 -0.1234')->__toString());
		$this->assertEquals('-0.123400, 0.123400', WG84DegreesService::parseCoords('-0.1234 0.1234')->__toString());
		$this->assertEquals('-0.123400, -0.123400', WG84DegreesService::parseCoords('-0.1234 -0.1234')->__toString());

		$this->assertEquals('1.123400, 0.123400', WG84DegreesService::parseCoords('1.1234 0.1234')->__toString());
		$this->assertEquals('1.123400, -0.123400', WG84DegreesService::parseCoords('1.1234 -0.1234')->__toString());
		$this->assertEquals('-1.123400, 0.123400', WG84DegreesService::parseCoords('-1.1234 0.1234')->__toString());
		$this->assertEquals('-1.123400, -0.123400', WG84DegreesService::parseCoords('-1.1234 -0.1234')->__toString());

		$this->assertEquals('0.123400, 2.123400', WG84DegreesService::parseCoords('0.1234 2.1234')->__toString());
		$this->assertEquals('0.123400, -2.123400', WG84DegreesService::parseCoords('0.1234 -2.1234')->__toString());
		$this->assertEquals('-0.123400, 2.123400', WG84DegreesService::parseCoords('-0.1234 2.1234')->__toString());
		$this->assertEquals('-0.123400, -2.123400', WG84DegreesService::parseCoords('-0.1234 -2.1234')->__toString());

		$this->assertEquals('10.123400, 0.123400', WG84DegreesService::parseCoords('10.1234 0.1234')->__toString());
		$this->assertEquals('10.123400, -0.123400', WG84DegreesService::parseCoords('10.1234 -0.1234')->__toString());
		$this->assertEquals('-10.123400, 0.123400', WG84DegreesService::parseCoords('-10.1234 0.1234')->__toString());
		$this->assertEquals('-10.123400, -0.123400', WG84DegreesService::parseCoords('-10.1234 -0.1234')->__toString());

		$this->assertEquals('0.123400, 10.123400', WG84DegreesService::parseCoords('0.1234 10.1234')->__toString());
		$this->assertEquals('0.123400, -10.123400', WG84DegreesService::parseCoords('0.1234 -10.1234')->__toString());
		$this->assertEquals('-0.123400, 10.123400', WG84DegreesService::parseCoords('-0.1234 10.1234')->__toString());
		$this->assertEquals('-0.123400, -10.123400', WG84DegreesService::parseCoords('-0.1234 -10.1234')->__toString());

		$this->assertEquals('10.123400, 10.123400', WG84DegreesService::parseCoords('10.1234 10.1234')->__toString());
		$this->assertEquals('10.123400, -10.123400', WG84DegreesService::parseCoords('10.1234 -10.1234')->__toString());
		$this->assertEquals('-10.123400, 10.123400', WG84DegreesService::parseCoords('-10.1234 10.1234')->__toString());
		$this->assertEquals('-10.123400, -10.123400', WG84DegreesService::parseCoords('-10.1234 -10.1234')->__toString());

		$this->assertEquals('89.999999, 99.123400', WG84DegreesService::parseCoords('89.999999 99.1234')->__toString());
		$this->assertEquals('89.999999, -99.123400', WG84DegreesService::parseCoords('89.999999 -99.1234')->__toString());
		$this->assertEquals('-89.999999, 99.123400', WG84DegreesService::parseCoords('-89.999999 99.1234')->__toString());
		$this->assertEquals('-89.999999, -99.123400', WG84DegreesService::parseCoords('-89.999999 -99.1234')->__toString());

		$this->assertEquals('90.000000, 99.123400', WG84DegreesService::parseCoords('90.000000 99.1234')->__toString());
		$this->assertEquals('90.000000, -99.123400', WG84DegreesService::parseCoords('90.000000 -99.1234')->__toString());
		$this->assertEquals('-90.000000, 99.123400', WG84DegreesService::parseCoords('-90.000000 99.1234')->__toString());
		$this->assertEquals('-90.000000, -99.123400', WG84DegreesService::parseCoords('-90.000000 -99.1234')->__toString());

		$this->assertEquals('89.999999, 100.123400', WG84DegreesService::parseCoords('89.999999 100.1234')->__toString());
		$this->assertEquals('89.999999, -100.123400', WG84DegreesService::parseCoords('89.999999 -100.1234')->__toString());
		$this->assertEquals('-89.999999, 100.123400', WG84DegreesService::parseCoords('-89.999999 100.1234')->__toString());
		$this->assertEquals('-89.999999, -100.123400', WG84DegreesService::parseCoords('-89.999999 -100.1234')->__toString());

		$this->assertEquals('89.999999, 179.999999', WG84DegreesService::parseCoords('89.999999 179.999999')->__toString());
		$this->assertEquals('89.999999, -179.999999', WG84DegreesService::parseCoords('89.999999 -179.999999')->__toString());
		$this->assertEquals('-89.999999, 179.999999', WG84DegreesService::parseCoords('-89.999999 179.999999')->__toString());
		$this->assertEquals('-89.999999, -179.999999', WG84DegreesService::parseCoords('-89.999999 -179.999999')->__toString());

		$this->assertEquals('89.999999, 180.000000', WG84DegreesService::parseCoords('89.999999 180.0')->__toString());
		$this->assertEquals('89.999999, -180.000000', WG84DegreesService::parseCoords('89.999999 -180.0')->__toString());
		$this->assertEquals('-89.999999, 180.000000', WG84DegreesService::parseCoords('-89.999999 180.0')->__toString());
		$this->assertEquals('-89.999999, -180.000000', WG84DegreesService::parseCoords('-89.999999 -180.0')->__toString());
		
		$this->assertEquals('90.000000, 180.000000', WG84DegreesService::parseCoords('90.0 180.0')->__toString());
		$this->assertEquals('90.000000, -180.000000', WG84DegreesService::parseCoords('90.0 -180.0')->__toString());
		$this->assertEquals('-90.000000, 180.000000', WG84DegreesService::parseCoords('-90.0 180.0')->__toString());
		$this->assertEquals('-90.000000, -180.000000', WG84DegreesService::parseCoords('-90.0 -180.0')->__toString());
	}


	public function testNothingInText(): void {
		$this->assertEquals([], WG84DegreesService::findInText('Nothing valid')->getAll());
	}

	public function testCoordinatesInText(): void {
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

		$betterLocations = WG84DegreesService::findInText($text);
		$this->assertEquals([50.1111, 10.2222], $betterLocations[0]->getLatLon());
		$this->assertEquals([-51.1111, -11.2222], $betterLocations[1]->getLatLon());
		$this->assertEquals([52.1111, 12.2222], $betterLocations[2]->getLatLon());
		$this->assertEquals([-53.1111, -13.2222], $betterLocations[3]->getLatLon());
		$this->assertEquals([54.1111, 14.2222], $betterLocations[4]->getLatLon());
		$this->assertEquals([-55.1111, -15.2222], $betterLocations[5]->getLatLon());
		$this->assertEquals([56.1111, 16.2222], $betterLocations[6]->getLatLon());
		$this->assertEquals([-57.1111, -17.2222], $betterLocations[7]->getLatLon());
		$this->assertEquals([-58.1111, 18.2222], $betterLocations[8]->getLatLon());
		$this->assertEquals([59.1111, -19.2222], $betterLocations[9]->getLatLon());

		$errors = $betterLocations->getErrors();
		$this->assertInstanceOf(InvalidLocationException::class, $errors[0]);
		$this->assertInstanceOf(InvalidLocationException::class, $errors[1]);
	}
}