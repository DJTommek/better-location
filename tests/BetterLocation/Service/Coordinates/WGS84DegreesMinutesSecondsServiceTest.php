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
		// @TODO add tests for this translition, which is currently used only in BetterLocationCollection::fromTelegramMessage() method
		// $this->assertSame('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::parseCoords('43°38′33.24″N 79°23′13.7″W')->__toString()); // special characters (″ !== ") and (′ !== ')  coords from Wikipedia
		$this->assertSame('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::processStatic('43°38\'33.24"N 79°23\'13.7"W')->getFirst()->__toString()); // same as above but already translited

		$this->assertSame('50.093653,14.412417', WGS84DegreesMinutesSecondsService::processStatic('50°5\'37.15" 14°24\'44.70"')->getFirst()->__toString());
//		$this->assertSame('50.093653,14.412417', WGS84DegreesMinutesSecondsService::parseCoords('50° 5\' 37.15" 14° 24\' 44.70"')->__toString()); // @TODO add this format

		$this->assertSame('10.000000,118.833333', WGS84DegreesMinutesSecondsService::processStatic('N 10°0\'0" E 118°50\'0"')->getFirst()->__toString()); // coordinates from La Casa de Papel
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
}
