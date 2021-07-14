<?php declare(strict_types=1);

use App\BetterLocation\Service\SygicService;
use PHPUnit\Framework\TestCase;

final class SygicServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://maps.sygic.com/#/?map=17,50.087451,14.420671&address=50.087451,14.420671', SygicService::getLink(50.087451, 14.420671));
		$this->assertSame('https://maps.sygic.com/#/?map=17,50.100000,14.500000&address=50.100000,14.500000', SygicService::getLink(50.1, 14.5));
		$this->assertSame('https://maps.sygic.com/#/?map=17,-50.200000,14.600000&address=-50.200000,14.600000', SygicService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://maps.sygic.com/#/?map=17,50.300000,-14.700001&address=50.300000,-14.700001', SygicService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://maps.sygic.com/#/?map=17,-50.400000,-14.800008&address=-50.400000,-14.800008', SygicService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->assertSame('https://go.sygic.com/navi/directions?to=50.087451,14.420671', SygicService::getLink(50.087451, 14.420671, true));
		$this->assertSame('https://go.sygic.com/navi/directions?to=50.100000,14.500000', SygicService::getLink(50.1, 14.5, true));
		$this->assertSame('https://go.sygic.com/navi/directions?to=-50.200000,14.600000', SygicService::getLink(-50.2, 14.6000001, true)); // round down
		$this->assertSame('https://go.sygic.com/navi/directions?to=50.300000,-14.700001', SygicService::getLink(50.3, -14.7000009, true)); // round up
		$this->assertSame('https://go.sygic.com/navi/directions?to=-50.400000,-14.800008', SygicService::getLink(-50.4, -14.800008, true));
	}
}
