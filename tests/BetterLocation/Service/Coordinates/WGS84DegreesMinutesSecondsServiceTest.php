<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesSecondsService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class WGS84DegreesMinutesSecondsServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link for raw coordinates is not supported.');
		WGS84DegreesMinutesSecondsService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link for raw coordinates is not supported.');
		WGS84DegreesMinutesSecondsService::getLink(50.087451, 14.420671, true);
	}

	public function testNothingInText(): void
	{
		$this->assertEquals([], WGS84DegreesMinutesSecondsService::findInText('Nothing valid')->getAll());
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testCoordinates(): void
	{
		// @TODO add tests for this translition, which is currently used only in BetterLocationCollection::fromTelegramMessage() method
		// $this->assertEquals('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::parseCoords('43°38′33.24″N 79°23′13.7″W')->__toString()); // special characters (″ !== ") and (′ !== ')  coords from Wikipedia
		$this->assertEquals('43.642567,-79.387139', WGS84DegreesMinutesSecondsService::parseCoords('43°38\'33.24"N 79°23\'13.7"W')->__toString()); // same as above but already translited

		$this->assertEquals('50.093653,14.412417', WGS84DegreesMinutesSecondsService::parseCoords('50°5\'37.15" 14°24\'44.70"')->__toString());
//		$this->assertEquals('50.093653,14.412417', WGS84DegreesMinutesSecondsService::parseCoords('50° 5\' 37.15" 14° 24\' 44.70"')->__toString()); // @TODO add this format
	}
}
