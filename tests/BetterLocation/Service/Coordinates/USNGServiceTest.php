<?php declare(strict_types=1);

use BetterLocation\Service\Coordinates\USNGService;
use BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../../../src/bootstrap.php';

final class USNGServiceTest extends TestCase
{
	public function testGenerateShareLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Share link for raw coordinates is not supported.');
		USNGService::getLink(50.087451, 14.420671);
	}

	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link for raw coordinates is not supported.');
		USNGService::getLink(50.087451, 14.420671, true);
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testCoordinatesFromGeocaching(): void {
		$this->assertEquals('50.079733,14.477500', USNGService::parseCoords('33U E 462616 N 5547626')->__toString()); // https://www.geocaching.com/geocache/GC19HCD_kafkuv-hrob-kafkas-grave
		$this->assertEquals('49.871733,18.423450', USNGService::parseCoords('34U E 314865 N 5527554')->__toString()); // https://www.geocaching.com/geocache/GCY3MG_orlova-jinak-orlovacity-otherwise
		$this->assertEquals('-51.692183,-57.856267', USNGService::parseCoords('21F E 440814 N 4272850')->__toString()); // https://www.geocaching.com/geocache/GC5HVVP_public-jetty
		$this->assertEquals('-45.873917,170.511983', USNGService::parseCoords('59G E 462125 N 4919845')->__toString()); // https://www.geocaching.com/geocache/GC8MFZX_otd-9-january-otago
		$this->assertEquals('41.882600,-87.623000', USNGService::parseCoords('16T E 448309 N 4636929')->__toString()); // https://www.geocaching.com/geocache/GCJZDR_cloud-gate-aka-the-bean
	}

	public function testNothingInText(): void {
		$this->assertEquals([], USNGService::findInText('Nothing valid')->getAll());
	}
}
