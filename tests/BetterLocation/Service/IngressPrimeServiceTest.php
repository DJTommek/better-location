<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\IngressPrimeService;
use PHPUnit\Framework\TestCase;

final class IngressPrimeServiceTest extends TestCase
{

	public function testGenerateShareLink(): void
	{
		$this->expectException(NotSupportedException::class);
		IngressPrimeService::getLink(50.087451, 14.420671, true);
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		IngressPrimeService::getLink(50.087451, 14.420671, true);
	}

	public function testIsValid(): void
	{
		$this->assertTrue(IngressPrimeService::isValidStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'));
		$this->assertTrue(IngressPrimeService::isValidStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12'));

		$this->assertFalse(IngressPrimeService::isValidStatic('non link'));
		$this->assertFalse(IngressPrimeService::isValidStatic('https://link.ingress.com/'));
		$this->assertFalse(IngressPrimeService::isValidStatic('https://ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'));
		$this->assertFalse(IngressPrimeService::isValidStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9999.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671'));
	}

	public function testProcess(): void
	{
		$collection = IngressPrimeService::processStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.087451%2C14.420671')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', (string)$collection->getFirst()->getCoordinates());

		$collection = IngressPrimeService::processStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420671', (string)$collection->getFirst()->getCoordinates());

		// Non existing GUID
		$collection = IngressPrimeService::processStatic('https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2Faaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa.12')->getCollection();
		$this->assertCount(0, $collection);
	}
}
