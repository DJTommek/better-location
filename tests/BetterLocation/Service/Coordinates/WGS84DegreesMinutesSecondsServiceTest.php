<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service\Coordinates;

use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesSecondsService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesMinutesSecondsServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreesMinutesSecondsService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WGS84DegreesMinutesSecondsService::getLink(50.087451, 14.420671, true);
	}

	public function testNothingInText(): void
	{
		$this->assertSame([], WGS84DegreesMinutesSecondsService::findInText('Nothing valid')->getLocations());
	}

	public function testCoordinates(): void
	{
		$this->assertSame('48.309400,16.861319', WGS84DegreesMinutesSecondsService::processStatic('48° 18′ 33.84″ N, 16° 51′ 40.75″ E')->getFirst()->__toString()); // special characters (″ !== ") and (′ !== ') with spaces between all parts
		$this->assertSame('48.309400,16.861319', WGS84DegreesMinutesSecondsService::findInText('48° 18′ 33.84″ N, 16° 51′ 40.75″ E')->getFirst()->__toString());
		$this->assertSame('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::processStatic('43°38′33.24″N 79°23′13.7″W')->getFirst()->__toString()); // special characters (″ !== ") and (′ !== ')  coords from Wikipedia
		$this->assertSame('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::processStatic('43°38\'33.24"N 79°23\'13.7"W')->getFirst()->__toString()); // same as above but already translited

		$this->assertSame('50.093653,14.412417', WGS84DegreesMinutesSecondsService::processStatic('50°5\'37.15" 14°24\'44.70"')->getFirst()->__toString());
//		$this->assertSame('50.093653,14.412417', WGS84DegreesMinutesSecondsService::parseCoords('50° 5\' 37.15" 14° 24\' 44.70"')->__toString()); // @TODO add this format

		$this->assertSame('10.000000,118.833333', WGS84DegreesMinutesSecondsService::processStatic('N 10°0\'0" E 118°50\'0"')->getFirst()->__toString()); // coordinates from La Casa de Papel

		// Coordinates formatted in exiftool output, eg 'exiftool photo.jpg -GPSPosition'
		$expectedCoords = '50.736192,15.739292';
		$this->assertSame($expectedCoords, WGS84DegreesMinutesSecondsService::processStatic('50 deg 44\' 10.29" N, 15 deg 44\' 21.45" E')->getFirst()->__toString());
		$text = <<<TEXT
exiftool 20190811_142950.jpg -GPSPosition
GPS Position                    : 50 deg 44' 10.29" N, 15 deg 44' 21.45" E
TEXT;
		$this->assertSame($expectedCoords, WGS84DegreesMinutesSecondsService::findInText($text)->getFirst()->__toString());
		$exiftoolOutput = file_get_contents(__DIR__ . '/fixtures/exiftool-output1.txt');
		$this->assertSame($expectedCoords, WGS84DegreesMinutesSecondsService::findInText($exiftoolOutput)->getFirst()->__toString());
	}

	/**
	 * Two single-quotes are just like one double-quote: '' -> "
	 */
	public function testDoubleQuote(): void
	{
		// Coordinates are from https://prazdnedomy.cz/domy/objekty/detail/2732-kasarna-u-sloupu
		$this->assertSame('49.060194,13.736222', WGS84DegreesMinutesSecondsService::processStatic('49° 3\' 36.7\'\', 13° 44\' 10.4\'\'')->getFirst()->__toString());

		$collection = WGS84DegreesMinutesSecondsService::findInText('You can find me on 49° 3\' 36.7\'\', 13° 44\' 10.4\'\' or somewhere near');
		$this->assertCount(1, $collection);
		$this->assertSame('49.060194,13.736222', $collection->getFirst()->__toString());
	}

	/**
	 * Prime (′, U+2032) instead of single-quote and double prime (″, U+2033) instead of double-quote, eg. from Wikipedia
	 */
	public static function primeSymbolsProvider(): array
	{
		return [
			// Spaces between every part and hemisphere as suffix
			['48.309400,16.861319', '48° 18′ 33.84″ N, 16° 51′ 40.75″ E'],
			['48.309400,16.861319', '48° 18′ 33.84″ N 16° 51′ 40.75″ E'],
			['48.309400,16.861319', '48° 18′ 33.84″ n, 16° 51′ 40.75″ e'],
			['-48.309400,16.861319', '48° 18′ 33.84″ S, 16° 51′ 40.75″ E'],
			['48.309400,-16.861319', '48° 18′ 33.84″ N, 16° 51′ 40.75″ W'],
			['-48.309400,-16.861319', '48° 18′ 33.84″ S, 16° 51′ 40.75″ W'],
			// Without any spaces
			['48.309400,16.861319', '48°18′33.84″N, 16°51′40.75″E'],
			['48.309400,16.861319', '48°18′33.84″N 16°51′40.75″E'],
			['48.309400,16.861319', '48°18′33.84″N,16°51′40.75″E'],
			// Hemisphere as prefix
			['48.309400,16.861319', 'N 48° 18′ 33.84″, E 16° 51′ 40.75″'],
			['48.309400,16.861319', 'N48°18′33.84″ E16°51′40.75″'],
			['-48.309400,-16.861319', 'S 48° 18′ 33.84″, W 16° 51′ 40.75″'],
			// Swapped order of coordinates
			['48.309400,16.861319', '16° 51′ 40.75″ E, 48° 18′ 33.84″ N'],
			['-48.309400,-16.861319', '16° 51′ 40.75″ W, 48° 18′ 33.84″ S'],
			// Without hemisphere
			['48.309400,16.861319', '48° 18′ 33.84″, 16° 51′ 40.75″'],
			['48.309400,16.861319', '48°18′33.84″ 16°51′40.75″'],
			// Mixed prime symbols with quotes
			['48.309400,16.861319', '48° 18′ 33.84″ N, 16° 51\' 40.75" E'],
			['48.309400,16.861319', '48° 18\' 33.84" N, 16° 51′ 40.75″ E'],
			['48.309400,16.861319', '48° 18′ 33.84" N, 16° 51\' 40.75″ E'],
			// Two primes instead of double prime
			['48.309400,16.861319', '48° 18′ 33.84′′ N, 16° 51′ 40.75′′ E'],
			// Whole seconds
			['48.309167,16.861111', '48° 18′ 33″ N, 16° 51′ 40″ E'],
			// Edge values
			['0.000000,0.000000', '0° 0′ 0″ N, 0° 0′ 0″ E'],
			['89.999997,179.999997', '89° 59′ 59.99″ N, 179° 59′ 59.99″ E'],
			['-89.999997,-179.999997', '89° 59′ 59.99″ S, 179° 59′ 59.99″ W'],
		];
	}

	/**
	 * @dataProvider primeSymbolsProvider
	 */
	public function testPrimeSymbols(string $expected, string $input): void
	{
		$service = new WGS84DegreesMinutesSecondsService();
		$service->setInput($input);
		$this->assertTrue($service->validate());

		$this->assertSame($expected, WGS84DegreesMinutesSecondsService::processStatic($input)->getFirst()->__toString());

		// Note: text must not contain letters N, S, E or W right before coordinates, otherwise they might be detected as hemisphere
		$collection = WGS84DegreesMinutesSecondsService::findInText('Look at ' . $input . ' today!');
		$this->assertCount(1, $collection);
		$this->assertSame($expected, $collection->getFirst()->__toString());
	}

	public function testPrimeSymbolsInvalid(): void
	{
		$service = new WGS84DegreesMinutesSecondsService();
		$service->setInput('48° 18′ 33.84″ N');
		$this->assertFalse($service->validate()); // Only one coordinate

		$service = new WGS84DegreesMinutesSecondsService();
		$service->setInput('48° 18′ 33.84″ N, 16° 51′ 40.75″ E, 49° 18′ 33.84″ N');
		$this->assertFalse($service->validate()); // Extra coordinate
	}

	public function testPrimeSymbolsFindMultipleInText(): void
	{
		$text = 'First location is 48° 18′ 33.84″ N, 16° 51′ 40.75″ E and second is 43°38′33.24″N 79°23′13.7″W, see you there.';
		$collection = WGS84DegreesMinutesSecondsService::findInText($text);
		$this->assertCount(2, $collection);
		$this->assertSame('48.309400,16.861319', $collection[0]->__toString());
		$this->assertSame('43.642567,-79.387139', $collection[1]->__toString());
	}
}
