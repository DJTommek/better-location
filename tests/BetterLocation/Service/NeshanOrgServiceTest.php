<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\NeshanOrgService;
use PHPUnit\Framework\TestCase;

final class NeshanOrgServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://neshan.org/maps/@50.087451,14.420671,11.0z,0.0p', NeshanOrgService::getLink(50.087451, 14.420671));
		$this->assertSame('https://neshan.org/maps/@50.100000,14.500000,11.0z,0.0p', NeshanOrgService::getLink(50.1, 14.5));
		$this->assertSame('https://neshan.org/maps/@-50.200000,14.600000,11.0z,0.0p', NeshanOrgService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://neshan.org/maps/@50.300000,-14.700001,11.0z,0.0p', NeshanOrgService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://neshan.org/maps/@-50.400000,-14.800008,11.0z,0.0p', NeshanOrgService::getLink(-50.4, -14.800008));
	}

	public function testIsValidMap(): void
	{
		$this->assertTrue(NeshanOrgService::validateStatic('https://neshan.org/maps/@50.087451,14.420671,11.0z,0.0p'));
		$this->assertTrue(NeshanOrgService::validateStatic('https://neshan.org/maps/@50.087451,14.420671'));
		$this->assertTrue(NeshanOrgService::validateStatic('http://neshan.org/maps/@50.087451,14.420671'));
		$this->assertTrue(NeshanOrgService::validateStatic('http://www.neshan.org/maps/@50.087451,14.420671'));
		$this->assertTrue(NeshanOrgService::validateStatic('https://neshan.org/maps/@-50.081311,-14.419521,12.7z,0.0p'));
		$this->assertTrue(NeshanOrgService::validateStatic('http://neshan.org/maps/50.087451,14.420671'));

		$this->assertFalse(NeshanOrgService::validateStatic('http://neshan.org/@50.087451,14.420671'));
		$this->assertFalse(NeshanOrgService::validateStatic('http://neshan.org/@50.087451,14.420671'));
	}

	public function testProcessSourceP(): void
	{
		$collection = NeshanOrgService::processStatic('https://neshan.org/maps/@50.087451,14.420671,11.0z,0.0p')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', (string)$collection->getFirst());

		$collection = NeshanOrgService::processStatic('https://neshan.org/maps/@35.822524,50.933589')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('35.822524,50.933589', (string)$collection->getFirst());
	}
}
