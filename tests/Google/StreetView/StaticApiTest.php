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
		$this->assertSame('CAoSLEFGMVFpcE9EVG1OWDFQRmVXT2NiN2lEZFY4bjktaXg5aXREd2lQWUYtNS1T', $result->pano_id);
		$this->assertSame(50.0892720720654, $result->location->lat);
		$this->assertSame(14.41848820220551, $result->location->lng);

		$result = self::$api->loadPanoaramaMetadataByCoords(-34.570368, -58.415685);
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('5W1yriPMzz1yKJdN6AKXEw', $result->pano_id);
		$this->assertSame(-34.5701839281276, $result->location->lat);
		$this->assertSame(-58.41561148540779, $result->location->lng);

		$result = self::$api->loadPanoaramaMetadataByCoords(55.123456, -31.123456);
		$this->assertNull($result);
	}
}
