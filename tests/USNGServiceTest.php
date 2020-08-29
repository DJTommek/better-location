<?php declare(strict_types=1);

use BetterLocation\Service\Coordinates\USNGService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config.php';

final class USNGServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testValidLocation(): void {
		$this->assertEquals('50.083718, 14.400509', USNGService::parseCoords('33 N 457111 5548111')->__toString()); // Prague
		$this->assertEquals('50.083718, 14.400509', USNGService::parseCoords('33N 457111 5548111')->__toString()); // Prague
		$this->assertEquals('50.083718, 14.400509', USNGService::parseCoords('33N457111 5548111')->__toString()); // Prague
	}


	public function testNothingInText(): void {
		$this->assertEquals([], USNGService::findInText('Nothing valid')->getAll());
	}
}