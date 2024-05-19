<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\DrobnePamatkyCzService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use PHPUnit\Framework\TestCase;

final class DrobnePamatkyCzServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.drobnepamatky.cz/blizko?km[latitude]=50.087451&km[longitude]=14.420671&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.drobnepamatky.cz/blizko?km[latitude]=50.100000&km[longitude]=14.500000&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.1, 14.5));
		$this->assertSame('https://www.drobnepamatky.cz/blizko?km[latitude]=-50.200000&km[longitude]=14.600000&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.drobnepamatky.cz/blizko?km[latitude]=50.300000&km[longitude]=-14.700001&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.drobnepamatky.cz/blizko?km[latitude]=-50.400000&km[longitude]=-14.800008&km[search_distance]=5&km[search_units]=km', DrobnePamatkyCzService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		DrobnePamatkyCzService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::validateStatic('http://www.drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::validateStatic('https://drobnepamatky.cz/node/36966'));
		$this->assertTrue(DrobnePamatkyCzService::validateStatic('http://drobnepamatky.cz/node/36966'));

		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/'));
		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/'));
		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/abc'));
		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/123abc'));
		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/abc123'));
		$this->assertFalse(DrobnePamatkyCzService::validateStatic('https://www.drobnepamatky.cz/node/123aaa456'));

		$this->assertFalse(DrobnePamatkyCzService::validateStatic('some invalid url'));
	}

	/**
	 * @group request
	 */
	public function testUrl(): void
	{
		$this->assertSame('50.067698,14.401455', DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/36966')->getFirst()->__toString());
		$this->assertSame('49.854263,18.542156', DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/9279')->getFirst()->__toString());
		$this->assertSame('49.805000,18.449748', DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/9282')->getFirst()->__toString());
		// Oborané památky (https://www.drobnepamatky.cz/oborane)
		$this->assertSame('49.687425,14.712345', DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/10646')->getFirst()->__toString());
		$this->assertSame('48.974158,14.612296', DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/2892')->getFirst()->__toString());
	}

	/**
	 * @group request
	 */
	public function testMissingCoordinates1(): void
	{
		$this->expectException(\App\MiniCurl\Exceptions\InvalidResponseException::class);
		$this->expectExceptionMessage('Invalid response code "404" but required "200" for URL "https://www.drobnepamatky.cz/node/9999999');
		DrobnePamatkyCzService::processStatic('https://www.drobnepamatky.cz/node/9999999');
	}

}
