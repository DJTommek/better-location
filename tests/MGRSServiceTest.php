<?php declare(strict_types=1);

use BetterLocation\Service\Coordinates\MGRSService;
use BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config.php';

final class MGRSServiceTest extends TestCase
{
	public function testGenerateShareLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link for raw coordinates is not supported.');
		MGRSService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link for raw coordinates is not supported.');
		MGRSService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testValidLocation(): void {
		$this->assertEquals('50.086359,14.408709', MGRSService::parseCoords('33UVR577484')->__toString()); // Prague
		$this->assertEquals('21.309433,-157.916867', MGRSService::parseCoords('4QFJ12345678')->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertEquals('21.309433,-157.916867', MGRSService::parseCoords('04QFJ12345678')->__toString()); // https://en.wikipedia.org/wiki/Military_Grid_Reference_System
		$this->assertEquals('38.959391,-95.265482', MGRSService::parseCoords('15SUD0370514711')->__toString());
		$this->assertEquals('38.889801,-77.036543', MGRSService::parseCoords('18SUJ2337106519')->__toString());
		$this->assertEquals('60.775935,4.693467', MGRSService::parseCoords('31VEH92233902')->__toString()); // Edge of Norway
		$this->assertEquals('-34.051387,18.462069', MGRSService::parseCoords('34HBH65742924')->__toString()); // South Africa
		$this->assertEquals('-45.892917,170.503103', MGRSService::parseCoords('59GMK61451773')->__toString()); // New Zeland
		// examples from https://www.usna.edu/Users/oceano/pguth/md_help/html/mgrs_utm.htm
//		$this->assertEquals(',', MGRSService::parseCoords('18SUJ7082315291')->__toString());
//		$this->assertEquals(',', MGRSService::parseCoords('18SUJ70821529')->__toString());
//		$this->assertEquals(',', MGRSService::parseCoords('18SUJ708152')->__toString());
	}

	public function testNothingInText(): void {
		$this->assertEquals([], MGRSService::findInText('Nothing valid')->getAll());
	}
}
