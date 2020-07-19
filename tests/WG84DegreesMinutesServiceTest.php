<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\Coordinates\WG84DegreesMinutesService;
use \BetterLocation\Service\Exceptions\InvalidLocationException;

require_once __DIR__ . '/../src/config.php';

final class WG84DegreesMinutesServiceTest extends TestCase
{
	public function testNothingInText(): void {
		$this->assertEquals([], WG84DegreesMinutesService::findInText('Nothing valid'));
	}

	public function testCoordinates(): void {
		$text = '';
		$text .= 'N50°59.72333\', E10°31.36987\'' . PHP_EOL;    // +/+
		$text .= 'N 51°4.34702\', E11°46.32372\'' . PHP_EOL;    // +/+
		$text .= 'S52°18.11425\', E 120°46.79265\'' . PHP_EOL;  // -/+
		$text .= 'S 53°37.66440\', W 13°13.32803\'' . PHP_EOL;  // -/-
		$text .= PHP_EOL;
		$text .= '54°59.72333\'N, 14°31.36987\'E' . PHP_EOL;    // +/+
		$text .= '55°4.34702\'N, 15°46.32372\'E' . PHP_EOL;     // +/+
		$text .= PHP_EOL;
		$text .= 'Invalid:';
		$text .= 'N56°18.11425\'S, 160°46.79265\' E' . PHP_EOL;     // Both coordinates are north-south hemisphere
		$text .= 'S56°18.11425\'S, 160°46.79265\' E' . PHP_EOL;     // Both coordinates are north-south hemisphere
		$text .= 'N56°18.11425\'S, E160°46.79265\' E' . PHP_EOL;    // Both coordinates are east-west hemisphere
		$text .= '57°37.66440\'S, E17°13.32803\'W' . PHP_EOL;       // Both coordinates are east-west hemisphere

		$betterLocations = WG84DegreesMinutesService::findInText($text);
		$this->assertEquals([50.99538883333334, 10.522831166666666], $betterLocations[0]->getLatLon());
		$this->assertEquals([51.072450333333336, 11.772062], $betterLocations[1]->getLatLon());
		$this->assertEquals([-52.30190416666667, 120.7798775], $betterLocations[2]->getLatLon());
		$this->assertEquals([-53.62774, -13.222133833333332], $betterLocations[3]->getLatLon());
		$this->assertEquals([54.99538883333334, 14.522831166666666], $betterLocations[4]->getLatLon());
		$this->assertEquals([55.072450333333336, 15.772062], $betterLocations[5]->getLatLon());
		$this->assertInstanceOf(InvalidLocationException::class, $betterLocations[6]);
		$this->assertInstanceOf(InvalidLocationException::class, $betterLocations[7]);
		$this->assertInstanceOf(InvalidLocationException::class, $betterLocations[8]);
		$this->assertInstanceOf(InvalidLocationException::class, $betterLocations[9]);
	}
}