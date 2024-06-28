<?php declare(strict_types=1);

namespace Tests\BetterLocation\Service;

use App\BetterLocation\Service\WaymarkingService;

final class WaymarkingServiceTest extends AbstractServiceTestCase
{
	protected function getServiceClass(): string
	{
		return WaymarkingService::class;
	}

	protected function getShareLinks(): array
	{
		$this->revalidateGeneratedShareLink = false;

		return [
			'https://www.waymarking.com/wm/search.aspx?lat=50.087451&lon=14.420671',
			'https://www.waymarking.com/wm/search.aspx?lat=50.100000&lon=14.500000',
			'https://www.waymarking.com/wm/search.aspx?lat=-50.200000&lon=14.600000', // round down
			'https://www.waymarking.com/wm/search.aspx?lat=50.300000&lon=-14.700001', // round up
			'https://www.waymarking.com/wm/search.aspx?lat=-50.400000&lon=-14.800008',
		];
	}

	protected function getDriveLinks(): array
	{
		return [];
	}

	public static function isWaymarkUrlProvider(): array
	{
		return [
			[true, 'https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[true, 'https://waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[true, 'http://WWW.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[true, 'http://waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[true, 'https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[true, 'https://www.waymarking.com/waymarks/WMDPMB'],

			[false, 'not valid url'],
			[false, 'https://www.waymarking.com'],
			[false, 'https://www.waymarking.com/waymarks/'],
			[false, 'https://www.waymarking.com/waymarks/AADPMB'],
		];
	}

	public static function isImageUrlProvider(): array
	{
		return [
			[true, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816&gid=3'],
			[true, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816'],

			[false, 'not valid url'],
		];
	}

	public static function isGalleryUrlProvider(): array
	{
		return [
			[true, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2'],
			[true, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=01645b8d-404c-41f1-a951-190032f55215&gid=2'],

			[false, 'not valid url'],
			[false, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b'],
			[false, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9baaaaa&gid=2'],
			[false, 'https://www.waymarking.com/gallery/default.aspx?f=2&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2'],
			[false, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=3'],
		];
	}

	public static function processWaymarkUrlProvider(): array
	{
		return [
			[49.198050, 16.607533, 'https://www.waymarking.com/waymarks/WMDPMB_Erb_Leopolda_Laanskho_Moravsk_nm_Brno_Czech_Republic'],
			[49.198050, 16.607533, 'https://www.waymarking.com/waymarks/WMDPMB'],
			[42.618700, -82.477967, 'https://www.waymarking.com/waymarks/wm10'],
			[47.685467, -122.249950, 'https://www.waymarking.com/waymarks/wm2'],
		];
	}

	public static function processImageUrlProvider(): array
	{
		return [
			[49.198050, 16.607533, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=14f4cb4b-1c0a-4b35-834a-b42e6baf2816&gid=3'],
			[42.618700, -82.477967, 'https://www.waymarking.com/gallery/image.aspx?f=1&guid=e1afc7aa-1dea-4110-9ae9-4d2b2c6deb68'],
		];
	}

	public function processGalleryUrlProvider(): array
	{
		return [
			[49.198050, 16.607533, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=9cc31753-8cfb-4b44-9c57-c2b303fd1a9b&gid=2'],
			[42.618700, -82.477967, 'https://www.waymarking.com/gallery/default.aspx?f=1&guid=01645b8d-404c-41f1-a951-190032f55215&gid=2'],
		];
	}

	/**
	 * @dataProvider isWaymarkUrlProvider
	 * @dataProvider isImageUrlProvider
	 * @dataProvider isGalleryUrlProvider
	 */
	public function testIsValid(bool $expectedIsValid, string $input): void
	{
		$service = new WaymarkingService();
		$this->assertServiceIsValid($service, $input, $expectedIsValid);
	}

	/**
	 * @group request
	 *
	 * @dataProvider processWaymarkUrlProvider
	 * @dataProvider processImageUrlProvider
	 * @dataProvider processGalleryUrlProvider
	 */
	public function testProcessReal(float $expectedLat, float $expectedLon, string $input): void
	{
		$service = new WaymarkingService();
		$this->assertServiceLocation($service, $input, $expectedLat, $expectedLon);
	}
}
