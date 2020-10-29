<?php declare(strict_types=1);

use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../src/bootstrap.php';

final class USNGServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link for raw coordinates is not supported.');
		USNGService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link for raw coordinates is not supported.');
		USNGService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testValidLocation(): void
	{
		$this->assertEquals('50.083718,14.400509', USNGService::parseCoords('33 N 457111 5548111')->__toString()); // Prague
		$this->assertEquals('50.083718,14.400509', USNGService::parseCoords('33N 457111 5548111')->__toString()); // Prague
		$this->assertEquals('50.083718,14.400509', USNGService::parseCoords('33N457111 5548111')->__toString()); // Prague
	}


	public function testNothingInText(): void
	{
		$this->assertEquals([], USNGService::findInText('Nothing valid')->getAll());
	}
}
