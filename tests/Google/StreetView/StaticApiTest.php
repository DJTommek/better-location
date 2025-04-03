<?php declare(strict_types=1);

namespace Tests\Google\StreetView;

use App\Config;
use App\Factory;
use App\Google\StreetView\StaticApi;
use DJTommek\Coordinates\CoordinatesInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group request
 */
final class StaticApiTest extends TestCase
{
	private static StaticApi $api;

	public static function setUpBeforeClass(): void
	{
		if (!Config::isGoogleStreetViewStaticApi()) {
			self::markTestSkipped('Missing Google API key');
		}

		self::$api = Factory::googleStreetViewApi();
	}

	public function testLookup(): void
	{
		$result = self::$api->loadPanoaramaMetadataByCoords(50.087451, 14.420671);
		$this->assertNotNull($result);
		$this->assertSame('CAoSLEFGMVFpcE04SXAyM09fVmlDTHZXSk9MX29oWFNtU3ZGRVFpZ1hvN0VSR3Z0', $result->pano_id);
		$this->assertInstanceOf(CoordinatesInterface::class, $result->location);
		$this->assertSame(50.08746650987292, $result->location->lat);
		$this->assertSame(14.42068342255011, $result->location->lon);

		$result = self::$api->loadPanoaramaMetadataByCoords(-34.570368, -58.415685);
		$this->assertNotNull($result);
		$this->assertSame('5W1yriPMzz1yKJdN6AKXEw', $result->pano_id);
		$this->assertInstanceOf(CoordinatesInterface::class, $result->location);
		$this->assertSame(-34.57018089509875, $result->location->lat);
		$this->assertSame(-58.41562184772004, $result->location->lon);

		$result = self::$api->loadPanoaramaMetadataByCoords(55.123456, -31.123456);
		$this->assertNull($result);
	}
}
