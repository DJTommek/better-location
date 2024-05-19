<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\IngressIntelService;
use PHPUnit\Framework\TestCase;

final class IngressIntelServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451,14.420671', IngressIntelService::getLink(50.087451, 14.420671));
		$this->assertSame('https://intel.ingress.com/?ll=50.100000,14.500000&pll=50.100000,14.500000', IngressIntelService::getLink(50.1, 14.5));
		$this->assertSame('https://intel.ingress.com/?ll=-50.200000,14.600000&pll=-50.200000,14.600000', IngressIntelService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://intel.ingress.com/?ll=50.300000,-14.700001&pll=50.300000,-14.700001', IngressIntelService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://intel.ingress.com/?ll=-50.400000,-14.800008&pll=-50.400000,-14.800008', IngressIntelService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		IngressIntelService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValidMap(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,144.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=-50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/intel?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.123456789,14.987654321'));

		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14.420671a'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451a,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=-150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.08.7451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14.420.671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.08745114.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451-14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?l=50.087451,14.420671'));
	}

	public function testIsValidPortal(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,144.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?pll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?pll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/intel?pll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14'));

		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671a'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451a,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.08.7451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420.671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.08745114.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451-14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?l=50.087451,14.420671'));
	}

	public function testIsValidPortalAndMap(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,144.420671&ll=50.087451,144.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,-14.420671&ll=50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-50.087451,14.420671&ll=-50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/intel?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50,14.420671&ll=50,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14&ll=50.087451,14'));

		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671a&ll=50.087451,14.420671a'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451a,14.420671&ll=50.087451a,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=150.087451,14.420671&ll=150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,214.420671&ll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=-150.087451,14.420671&ll=-150.087451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,214.420671&ll=50.087451,214.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.08.7451,14.420671&ll=50.08.7451,14.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420.671&ll=50.087451,14.420.671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.08745114.420671&ll=50.08745114.420671'));
		$this->assertFalse(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451-14.420671&ll=50.087451-14.420671'));
	}

	public function testIsValidOnlyPortal(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671&ll=fdassafd'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?pll=50.087451,14.420671&ll=50.087451----14.420671'));
	}

	public function testIsValidOnlyMap(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14.420671&pll=fdassafd'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/?ll=50.087451,14.420671&pll=50.087451----14.420671'));
	}

	/**
	 * @group request
	 */
	public function testProcessMap(): void
	{
		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?ll=50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection[0]->__toString());

		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?ll=50.123456789,14.987654321')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.123457,14.987654', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testProcessCoords(): void
	{
		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?pll=50.087451,14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', $collection[0]->__toString());

		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?pll=50.123456789,14.987654321')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.123457,14.987654', $collection[0]->__toString());
	}

	/**
	 * @group request
	 */
	public function testProcessMapAndCoords(): void
	{
		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?ll=50.123456789,14.987654321&pll=43.123456789,12.987654321')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('43.123457,12.987654', $collection[0]->__toString());
		$this->assertSame('50.123457,14.987654', $collection[1]->__toString());

		$collection = IngressIntelService::processStatic('https://intel.ingress.com/?pll=-0.11,14.987654321&ll=89.123456789,12.987654321')->getCollection();
		$this->assertCount(2, $collection);
		$this->assertSame('-0.110000,14.987654', $collection[0]->__toString());
		$this->assertSame('89.123457,12.987654', $collection[1]->__toString());
	}

	/**
	 * Old format without subdomain "intel." but with path "/intel".
	 * As of 2022-05-22 "/intel" redirects to "intel.ingress.com/intel"
	 */
	public function testIsValidOldFormat(): void
	{
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/intel?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/intel?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://intel.ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('http://intel.ingress.com/intel?pll=-50.087451,-14.420671&ll=-50.087451,-14.420671'));

		// As of 2022-05-22 links are not valid, but process them as ok
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/?pll=50.087451,14.420671&ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/?ll=50.087451,14.420671'));
		$this->assertTrue(IngressIntelService::validateStatic('https://ingress.com/?pll=50.087451,14.420671'));
	}
}
