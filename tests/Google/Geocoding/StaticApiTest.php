<?php declare(strict_types=1);

namespace Tests\Google\Geocoding;

use App\Config;
use App\Factory;
use App\Google\Geocoding\GeocodeResponse;
use App\Google\Geocoding\StaticApi;
use PHPUnit\Framework\TestCase;

/**
 * @group request
 */
final class StaticApiTest extends TestCase
{
	private static StaticApi $api;

	public static function setUpBeforeClass(): void
	{
		if (!Config::isGooglePlaceApi()) {
			self::markTestSkipped('Missing Google API key');
		}

		self::$api = Factory::googleGeocodingApi();
	}

	/**
	 * @return array<array{string, string, string, float, float}>
	 */
	public static function reverseValidProvider(): array
	{
		return [
			['Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia', '9F2P3CPC+X7M', '3CPC+X7M Prague, Czechia', 50.087451, 14.420671],
			['2PFM+75 Doubek, Czechia', '9F2P2PFM+75C', '2PFM+75C Doubek, Czechia', 50.023194, 14.732896],
			['35 Bathurst St, Richmond TAS 7025, Australia', '4R997C7Q+FPC', '7C7Q+FPC Richmond TAS, Australia', -42.7363111, 147.4392722],
		];
	}

	/**
	 * @return array<array{float, float}>
	 */
	public static function reverseInvalidProvider(): array
	{
		return [
			[0.123, 0.123],
		];
	}

	/**
	 * @dataProvider reverseValidProvider
	 */
	public function testReverseValid(string $expectedAddress, string $expectedPlusCode, string $expectedPlusCodeCompound, float $lat, float $lon): void
	{
		$coords = new \DJTommek\Coordinates\Coordinates($lat, $lon);
		$response = self::$api->reverse($coords);
		$this->assertInstanceOf(GeocodeResponse::class, $response);
		$this->assertSame($expectedAddress, $response->results[0]->formatted_address);
		$this->assertSame($expectedPlusCode, $response->plus_code->global_code);
		$this->assertSame($expectedPlusCodeCompound, $response->plus_code->compound_code);
	}

	/**
	 * @dataProvider reverseInvalidProvider
	 */
	public function testReverseInvalid(float $lat, float $lon): void
	{
		$coords = new \DJTommek\Coordinates\Coordinates($lat, $lon);
		$response = self::$api->reverse($coords);
		$this->assertNull($response);
	}
}
