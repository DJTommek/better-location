<?php declare(strict_types=1);

use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\OrganicMapsService;
use PHPUnit\Framework\TestCase;

final class OrganicMapsServiceTest extends TestCase
{
	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://omaps.app/s4G4aoSWF9', OrganicMapsService::getLink(50.087451, 14.420671));
		$this->assertSame('https://omaps.app/s4G4wVeaYf', OrganicMapsService::getLink(50.1, 14.5));
		$this->assertSame('https://omaps.app/sSsSWidvhu', OrganicMapsService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://omaps.app/stTvAx92Os', OrganicMapsService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://omaps.app/sH5E3Q-3bx', OrganicMapsService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotImplementedException::class);
		$this->expectExceptionMessage('Drive link is not implemented.');
		OrganicMapsService::getLink(50.087451, 14.420671, true);
	}


	/**
	 * Generate random coordinate, convert them to code, this code convert back to coordinates and compare them with these randomly generated.
	 * Aaaaaand do it multiple time.
	 */
	public function testGenerateAndParse(): void
	{
		for ($i = 0; $i < 10000; $i++) {
			$originalLat = $this->generateRandomLat();
			$originalLon = $this->generateRandomLon();
			$generatedLink = OrganicMapsService::getLink($originalLat, $originalLon);
			$parsedLocation = OrganicMapsService::processStatic($generatedLink)->getFirst();
			// precision might be lost while converting back and forth (eg 50.042366 can became 50.042365 or 50.042367)
			$this->assertEqualsWithDelta($originalLat, $parsedLocation->getLat(), 0.00001);
			$this->assertEqualsWithDelta($originalLon, $parsedLocation->getLon(), 0.00001);
		}
	}


	public function testIsValidUrl(): void
	{
		$this->assertTrue(OrganicMapsService::isValidStatic('https://omaps.app/s4G4aoSWF9'));
		$this->assertTrue(OrganicMapsService::isValidStatic('https://omaps.app/44G4aoThBC/Jan_Hus_Memorial'));
		$this->assertTrue(OrganicMapsService::isValidStatic('https://omaps.app/0abcdefghi'));
		$this->assertTrue(OrganicMapsService::isValidStatic('https://omaps.app/sa'));

		$this->assertFalse(OrganicMapsService::isValidStatic('https://www.omaps.app/s4G4aoSWF9'));
		$this->assertFalse(OrganicMapsService::isValidStatic('https://omaps.app/saabbccddaabbccddaabbccddaa'));
		$this->assertFalse(OrganicMapsService::isValidStatic('https://omaps.app'));
		$this->assertFalse(OrganicMapsService::isValidStatic('https://omaps.app/'));
		$this->assertFalse(OrganicMapsService::isValidStatic('https://omaps.app/s'));
	}

	public function testProcessUrl(): void
	{
		$collection = OrganicMapsService::processStatic('http://omaps.app/s4G4aoSWF9')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087451,14.420670', $collection->getFirst()->__toString());

		$collection = OrganicMapsService::processStatic('http://omaps.app/aaaaaaaaaa')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('-12.857143,25.714286', $collection->getFirst()->__toString());

		// with name
		$collection = OrganicMapsService::processStatic('http://omaps.app/44G4aoThBC/Jan_Hus_Memorial')->getCollection();
		$this->assertCount(1, $collection);
		$this->assertSame('50.087702,14.421123', $collection->getFirst()->__toString());
	}

	private function generateRandomLat(): float
	{
		return rand(-89999999, 89999999) / 1000000;
	}

	private function generateRandomLon(): float
	{
		return rand(-179999999, 179999999) / 1000000;
	}
}
