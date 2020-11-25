<?php declare(strict_types=1);

use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class DrobnePamatkyCzServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertEquals('https://www.drobnepamatky.cz/blizko?km[latitude]=50.087451&km[longitude]=14.420671&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://www.drobnepamatky.cz/blizko?km[latitude]=50.100000&km[longitude]=14.500000&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.1, 14.5));
		$this->assertEquals('https://www.drobnepamatky.cz/blizko?km[latitude]=-50.200000&km[longitude]=14.600000&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://www.drobnepamatky.cz/blizko?km[latitude]=50.300000&km[longitude]=-14.700001&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://www.drobnepamatky.cz/blizko?km[latitude]=-50.400000&km[longitude]=-14.800008&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		DrobnePamatkyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::isValid('http://www.drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::isValid('https://drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::isValid('http://drobnepamatky.cz/node/36966'));

		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/'));
		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/'));
		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/abc'));
		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/123abc'));
		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/abc123'));
		$this->assertFalse(DrobnePamatkyCzService::isValid('https://www.drobnepamatky.cz/node/123aaa456'));

		$this->assertFalse(DrobnePamatkyCzService::isValid('some invalid url'));
	}

	/** @noinspection PhpUnhandledExceptionInspection */
	public function testUrl(): void
	{
		$this->assertEquals('50.067665,14.401487', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/36966')->__toString());
		$this->assertEquals('49.703025,13.215935', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/40369')->__toString());
		$this->assertEquals('49.854270,18.542159', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/9279')->__toString());
		$this->assertEquals('49.805000,18.449748', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/9282')->__toString());
		// Oborané památky (https://www.drobnepamatky.cz/oborane)
		$this->assertEquals('49.687435,14.712323', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/10646')->__toString());
		$this->assertEquals('48.974158,14.612296', DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/2892')->__toString());
	}

	public function testMissingCoordinates1(): void
	{
		$this->expectException(InvalidLocationException::class);
		$this->expectExceptionMessage('Unable to get coords from DrobnePamatky.cz link https://www.drobnepamatky.cz/node/9999999.');
		DrobnePamatkyCzService::parseCoords('https://www.drobnepamatky.cz/node/9999999');
	}

}
