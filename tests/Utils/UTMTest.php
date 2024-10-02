<?php declare(strict_types=1);

namespace Tests\Utils;

use App\Utils\UTM;
use App\Utils\UTMFormat;
use DJTommek\Coordinates\Coordinates;
use PHPUnit\Framework\TestCase;

final class UTMTest extends TestCase
{
	public function dataProvider(): array
	{
		return [
			[49.718209, 13.347702, '33U 380896 5508610'],
			[43.642567, -79.387139, '17T 630084 4833438'],
			[-43.642567, -79.387139, '17G 630084 5166561'],
			[53.855000, 8.081667, '32U 439595 5967780'],
			// @TODO handle special case, Norway has bigger area
			// [59.447292, 5.392857, '32V 295520 6595405'],

			[51.1657, 10.4515, '32U 601485 5669253'],
			[48.858844, 2.294351, '31U 448241 5412004'],
			[34.052235, -118.243683, '11S 385215 3768645'],

			[28.343229, -172.883328, '2R 315398 3136665'],
			[11.56079, -140.813723, '7P 520310 1277994'],
			[19.065399, 71.465696, '42Q 759475 2109888'],
			[76.145344, 101.346017, '47X 562694 8452649'],
			[-6.732803, 147.003422, '55M 500378 9255788'],
			[-42.458994, 3.379231, '31G 531180 5299190'],
			[-43.119946, -153.974236, '5G 420746 5225404'],
			[-47.485818, -29.356318, '26G 322494 4738155'],

			// Random coordinates generated for each zone band (letter)
			[-79.4551, 98.038719, '47C 480361 1179070'],
			[-74.12011, 10.243249, '32C 537968 1774165'],
			[-69.23316, -104.550733, '13D 517777 2319573'],
			[-64.66738, 97.099863, '47D 409293 2827254'],
			[-59.65725, 154.335724, '56E 575269 3386001'],
			[-54.86618, 160.909503, '57F 622546 3918429'],
			[-49.47125, -70.966071, '19F 357562 4518297'],
			[-44.72212, 124.337673, '51G 605937 5047047'],
			[-39.99424, -21.541591, '27H 453765 5572741'],
			[-34.30567, 84.483504, '45H 268415 6201083'],
			[-29.88134, 133.492921, '53J 354464 6693409'],
			[-24.1386, 115.895333, '50J 387759 7329985'],
			[-19.69736, -44.921469, '23K 508230 7822006'],
			[-14.54447, -151.712229, '5L 638743 8391665'],
			[-9.10643, 127.936162, '52L 383097 8993209'],
			[-4.89986, 27.079196, '35M 508780 9458404'],
			[1.83789, -129.863284, '9N 403983 203165'],
			[6.9004, -35.88455, '25N 181200 763703'],
			[11.49459, -93.11394, '15P 487573 1270669'],
			[16.8315, -147.974984, '6Q 396123 1861170'],
			[21.32224, 14.087852, '33Q 405403 2358085'],
			[26.94764, -9.710927, '29R 429431 2980834'],
			[31.62073, -169.514732, '2R 640873 3499355'],
			[36.46981, 31.865093, '36S 398321 4036657'],
			[41.91575, 155.683145, '56T 722513 4643904'],
			[46.91959, -26.679079, '26T 524434 5196278'],
			[51.85331, -113.817696, '12U 305950 5748476'],
			[56.54726, 56.600185, '40V 475417 6267062'],
			[61.86539, 53.375328, '39V 624941 6861468'],
			[66.4605, 122.866583, '51W 494054 7371246'],
			[71.20725, 0.966505, '31W 426898 7901739'],
			[76.92585, 83.795484, '44X 570566 8540175'],
			[81.12345, 5.986, '31X 551416 9008308'],
			[83.446918, -83.93357, '17X 462638 9267300'],

			// Edge cases
			[0.000001, 0.000001, '31N 166022 0'],
			[-0.000001, 0.000001, '31M 166022 9999999'],
			[0.000001, -0.000001, '30N 833977 0'],
			[-0.000001, -0.000001, '30M 833977 9999999'],

			[83.999999, 12.345678, '33X 469034 9328807'],
			[-79.999999, 12.345678, '33C 448561 1117240'],
		];
	}

	public function coordinatesOufOfRangeProvider(): array
	{
		return [
			[-80.18638, -163.742394], // probably might be '3Z 523927 1097352'
			[-80.71542, -111.500367], // probably might be '12Z 490987 1038521'
		];
	}

	public function invalidUtm(): array
	{
		return [
			[-1, 'N', 123456, 1234567, 'Zone number "-1" is out of allowed range.'],
			[61, 'N', 123456, 1234567, 'Zone number "61" is out of allowed range.'],

			[30, 'A', 123456, 1234567, 'UTM Zone band "A" is not valid.'],
			[30, 'I', 123456, 1234567, 'UTM Zone band "I" is not valid.'],
			[30, 'n', 123456, 1234567, 'UTM Zone band "n" is not valid.'],

			[30, 'N', -10, 1234567, 'Easting "-10" is out of allowed range.'],
			[30, 'N', 999_999, 1234567, 'Easting "999999" is out of allowed range.'],
			[30, 'N', 123456, -10, 'Northing "-10" is out of allowed range.'],
			[30, 'N', 123456, 10_000_000, 'Northing "10000000" is out of allowed range.'],
		];
	}

	/**
	 * @dataProvider dataProvider
	 */
	public final function testLatLonToUTM(float $lat, float $lon, string $expectedUtm): void
	{
		$utm = UTM::fromCoordinates(new Coordinates($lat, $lon));
		$this->assertSame($expectedUtm, $utm->format(UTMFormat::ZONE_COORDS));
	}

	/**
	 * @dataProvider dataProvider
	 */
	public final function testUTMToLatLon(float $expectedLat, float $expectedLon, string $utmString): void
	{
		$utm = $this->utmFromString($utmString);
		$this->assertEqualsWithDelta($expectedLat, $utm->getLat(), 0.0001);
		$this->assertEqualsWithDelta($expectedLon, $utm->getLon(), 0.0001);
	}

	/**
	 * @dataProvider coordinatesOufOfRangeProvider
	 */
	public final function testCoordsOutOfRange(float $lat, float $lon): void
	{
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessageMatches('/^Latitude .+ is out of range$/');
		UTM::fromCoordinates(new Coordinates($lat, $lon));
	}

	/**
	 * @dataProvider invalidUtm
	 */
	public final function testInvalidUtm(int $zoneNumber, string $zoneBand, int $easting, int $northing, string $expectedMessage): void
	{
		$this->expectException(\InvalidArgumentException::class);
//		$this->expectExceptionMessageMatches('/^Latitude .+ is out of range$/');
		$this->expectExceptionMessage($expectedMessage);
		new UTM($zoneNumber, $zoneBand, $easting, $northing);
	}

	private function utmFromString(string $utmString): UTM
	{
		assert((bool)preg_match('/^([0-9]{1,2})([A-Z]) ([0-9]{6}) ([0-9]{1,7})$/', $utmString, $matches));
		return new UTM(
			zoneNumber: (int)$matches[1],
			zoneBand: $matches[2],
			easting: (int)$matches[3],
			northing: (int)$matches[4],
		);
	}
}
