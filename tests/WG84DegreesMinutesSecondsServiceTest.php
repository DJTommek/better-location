<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\Coordinates\WG84DegreesMinutesSecondsService;

require_once __DIR__ . '/../src/config.php';

final class WG84DegreesMinutesServiceSecondsTest extends TestCase
{
	public function testNothingInText(): void {
		$this->assertEquals([], WG84DegreesMinutesSecondsService::findInText('Nothing valid')->getAll());
	}

	public function testCoordinates(): void {
		// @TODO add tests for this translition, which is currently used only in generateFromTelegramMessage() method
		// $this->assertEquals('43.642567, -79.387139', WG84DegreesMinutesSecondsService::parseCoords('43°38′33.24″N 79°23′13.7″W')->__toString()); // special characters (″ !== ") and (′ !== ')  coords from Wikipedia
		$this->assertEquals('43.642567, -79.387139', WG84DegreesMinutesSecondsService::parseCoords('43°38\'33.24"N 79°23\'13.7"W')->__toString()); // same as above but already translited
	}
}