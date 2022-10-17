<?php declare(strict_types=1);

namespace Tests\Google\StreetView;

use App\Config;
use App\Google\StreetView\StaticApi;
use PHPUnit\Framework\TestCase;

/**
 * @group request
 */
final class StaticApiTest extends TestCase
{
	private static StaticApi $api;

	public static function setUpBeforeClass(): void
	{
		if (is_null(Config::GOOGLE_PLACE_API_KEY)) {
			self::markTestSkipped('Missing Google API key');
		}
		self::$api = new StaticApi();
	}

	public function testLookup(): void
	{
		$result = self::$api->loadPanoaramaMetadataByCoords(50.087451, 14.420671);
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('CAoSLEFGMVFpcE04SXAyM09fVmlDTHZXSk9MX29oWFNtU3ZGRVFpZ1hvN0VSR3Z0', $result->pano_id);
		$this->assertSame(50.0874665, $result->location->lat);
		$this->assertSame(14.4206834, $result->location->lng);

		$result = self::$api->loadPanoaramaMetadataByCoords(-34.570368, -58.415685);
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('5W1yriPMzz1yKJdN6AKXEw', $result->pano_id);
		$this->assertSame(-34.5701839281276, $result->location->lat);
		$this->assertSame(-58.41561148540779, $result->location->lng);

		$result = self::$api->loadPanoaramaMetadataByCoords(55.123456, -31.123456);
		$this->assertNull($result);
	}
}
