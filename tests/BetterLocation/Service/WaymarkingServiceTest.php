<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Service\WaymarkingService;
use PHPUnit\Framework\TestCase;

final class WaymarkingServiceTest extends TestCase
{
	private function assertLocation(float $expectedLat, float $expectedLon, string $input)
	{
		$locations = WaymarkingService::processStatic($input)->getCollection();
		$this->assertCount(1, $locations);
		$location = $locations->getFirst();
		$this->assertEqualsWithDelta($expectedLat, $location->getLat(), 0.000001);
		$this->assertEqualsWithDelta($expectedLon, $location->getLon(), 0.000001);
	}

	public function testGenerateShareLink(): void
	{
		$this->assertSame('https://www.waymarking.com/wm/search.aspx?lat=50.087451&lon=14.420671', WaymarkingService::getLink(50.087451, 14.420671));
		$this->assertSame('https://www.waymarking.com/wm/search.aspx?lat=50.100000&lon=14.500000', WaymarkingService::getLink(50.1, 14.5));
		$this->assertSame('https://www.waymarking.com/wm/search.aspx?lat=-50.200000&lon=14.600000', WaymarkingService::getLink(-50.2, 14.6000001)); // round down
		$this->assertSame('https://www.waymarking.com/wm/search.aspx?lat=50.300000&lon=-14.700001', WaymarkingService::getLink(50.3, -14.7000009)); // round up
		$this->assertSame('https://www.waymarking.com/wm/search.aspx?lat=-50.400000&lon=-14.800008', WaymarkingService::getLink(-50.4, -14.800008));
	}

	public function testGenerateDriveLink(): void
	{
		$this->expectException(NotSupportedException::class);
		WaymarkingService::getLink(50.087451, 14.420671, true);
	}

	public function testIsWaymarkUrl(): void
	{
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'));
		$this->assertTrue(WaymarkingService::isValidStatic('https://waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'));
		$this->assertTrue(WaymarkingService::isValidStatic('http://WWW.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'));
		$this->assertTrue(WaymarkingService::isValidStatic('http://waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'));
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'));
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/waymarks/WMDPMB'));

		$this->assertFalse(WaymarkingService::isValidStatic('not valid url'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/waymarks/'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/waymarks/AADPMB'));
	}

	public function testIsImageUrl(): void
	{
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816&gid=3'));
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816'));

		$this->assertFalse(WaymarkingService::isValidStatic('not valid url'));
	}

	public function testIsGalleryUrl(): void
	{
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2'));
		$this->assertTrue(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=1&guid=01645b8d-404c-41f1-a951-190032f55215&gid=2'));

		$this->assertFalse(WaymarkingService::isValidStatic('not valid url'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9baaaaa&gid=2'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=2&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2'));
		$this->assertFalse(WaymarkingService::isValidStatic('https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=3'));
	}

	/**
	 * @group request
	 */
	public function testWaymark(): void
	{
		$this->assertLocation(49.198050, 16.607533, 'https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic');
		$this->assertLocation(49.198050, 16.607533, 'https://www.waymarking.com/waymarks/WMDPMB');
		$this->assertLocation(42.618700, -82.477967, 'https://www.waymarking.com/waymarks/wm10');
		$this->assertLocation(47.685467, -122.249950, 'https://www.waymarking.com/waymarks/wm2');
	}

	/**
	 * @group request
	 */
	public function testImage(): void
	{
		$this->assertLocation(49.198050, 16.607533, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816&gid=3');
		$this->assertLocation(42.618700, -82.477967, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=e1afc7aa-1dea-4110-9ae9-4d2b2c6deb68');
	}

	/**
	 * @group request
	 */
	public function testGallery(): void
	{
		$this->assertLocation(49.198050, 16.607533, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2');
		$this->assertLocation(42.618700, -82.477967, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=01645b8d-404c-41f1-a951-190032f55215&gid=2');
	}
}
