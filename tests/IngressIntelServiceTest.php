<?php declare(strict_types=1);

use BetterLocation\Service\Exceptions\NotSupportedException;
use BetterLocation\Service\IngressIntelService;
use BetterLocation\Service\WikipediaService;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../src/config.php';


final class IngressIntelServiceTest extends TestCase
{
	/** @noinspection PhpUnhandledExceptionInspection */
	public function testGenerateShareLink(): void {
		$this->assertEquals('https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671', IngressIntelService::getLink(50.087451, 14.420671));
		$this->assertEquals('https://intel.ingress.com/?ll=50.100000,14.500000&pll=50.100000,14.500000', IngressIntelService::getLink(50.1, 14.5));
		$this->assertEquals('https://intel.ingress.com/?ll=-50.200000,14.600000&pll=-50.200000,14.600000', IngressIntelService::getLink(-50.2, 14.6000001)); // round down
		$this->assertEquals('https://intel.ingress.com/?ll=50.300000,-14.700001&pll=50.300000,-14.700001', IngressIntelService::getLink(50.3, -14.7000009)); // round up
		$this->assertEquals('https://intel.ingress.com/?ll=-50.400000,-14.800008&pll=-50.400000,-14.800008', IngressIntelService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void {
		$this->expectException(NotSupportedException::class);
		$this->expectExceptionMessage('Drive link is not supported.');
		WikipediaService::getLink(50.087451, 14.420671, true);
	}
}
