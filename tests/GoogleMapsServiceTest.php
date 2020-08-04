<?php declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use \BetterLocation\Service\GoogleMapsService;
use \BetterLocation\Service\Exceptions\InvalidLocationException;

require_once __DIR__ . '/../src/config.php';

final class GoogleMapsServiceTest extends TestCase
{
	/**
	 * @TODO Disabled due to oossibly too many requests to Google servers (recaptcha appearing...)
	 * @noinspection PhpUnhandledExceptionInspection
	 */
//	public function testShortUrl(): void {
//		$this->assertEquals('49.982825, 14.571417', GoogleMapsService::parseCoords('https://goo.gl/maps/rgZZt125tpvf2rnCA')->__toString());
//		$this->assertEquals('49.306603, 14.146709', GoogleMapsService::parseCoords('https://goo.gl/maps/eUYMwABdpv9NNSDX7')->__toString());
//		$this->assertEquals('49.306233, 14.146671', GoogleMapsService::parseCoords('https://goo.gl/maps/hEbUKxSuMjA2')->__toString());
//		$this->assertEquals('49.270226, 14.046216', GoogleMapsService::parseCoords('https://goo.gl/maps/pPZ91TfW2edvejbb6')->__toString());
//		$this->assertEquals('49.296449, 14.480361', GoogleMapsService::parseCoords('https://maps.app.goo.gl/W5wPRJ5FMJxgaisf9')->__toString());
//		$this->assertEquals('49.267720, 14.003169', GoogleMapsService::parseCoords('https://maps.app.goo.gl/nJqTbFow1HtofApTA')->__toString());
//	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testNormalUrl(): void {
		$this->assertEquals('49.294166, 14.225833', GoogleMapsService::parseCoords('https://www.google.com/maps/place/Velk%C3%BD+Meheln%C3%ADk,+397+01+Pisek/@49.2941662,14.2258333,14z/data=!4m2!3m1!1s0x470b5087ca84a6e9:0xfeb1428d8c8334da')->__toString());
		$this->assertEquals('49.211328, 14.255349', GoogleMapsService::parseCoords('https://www.google.com/maps/place/Zelend%C3%A1rky/@49.2069545,14.2495123,15z/data=!4m5!3m4!1s0x0:0x3ad3965c4ecb9e51!8m2!3d49.2113282!4d14.2553488')->__toString());
		$this->assertEquals('36.826460, 22.528715', GoogleMapsService::parseCoords('https://www.google.cz/maps/@36.8264601,22.5287146,9.33z')->__toString());
		$this->assertEquals('49.333511, 14.296174', GoogleMapsService::parseCoords('https://www.google.cz/maps/place/49%C2%B020\'00.6%22N+14%C2%B017\'46.2%22E/@49.3339819,14.2956352,18.4z/data=!4m5!3m4!1s0x0:0x0!8m2!3d49.333511!4d14.296174')->__toString());
		$this->assertEquals('49.308853, 14.146589', GoogleMapsService::parseCoords('https://www.google.cz/maps/place/Hrad+P%C3%ADsek/@49.3088543,14.1454615,391m/data=!3m1!1e3!4m12!1m6!3m5!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!2sHrad+P%C3%ADsek!8m2!3d49.3088525!4d14.1465894!3m4!1s0x470b4ff494c201db:0x4f78e2a2eaa0955b!8m2!3d49.3088525!4d14.1465894')->__toString());
		$this->assertEquals('49.367523, 14.514022', GoogleMapsService::parseCoords('https://maps.google.com/maps?ll=49.367523,14.514022&q=49.367523,14.514022')->__toString());
	}

}