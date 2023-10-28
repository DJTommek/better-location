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
	 * @return array<array{string, string, string, string|null, float, float}>
	 */
	public static function reverseValidProvider(): array
	{
		return [
			['Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia', '9F2P3CPC+X7M', '3CPC+X7M Prague, Czechia', '🇨🇿', 50.087451, 14.420671],
			['2PFM+75 Doubek-Říčany u Prahy, Czechia', '9F2P2PFM+75C', '2PFM+75C Doubek-Říčany u Prahy, Czechia', '🇨🇿', 50.023194, 14.732896],
			['35 Bathurst St, Richmond TAS 7025, Australia', '4R997C7Q+FPC', '7C7Q+FPC Richmond TAS, Australia', '🇦🇺', -42.7363111, 147.4392722],
			['8FMP7C9C+77', '8FMP7C9C+773', '8FMP7C9C+773', null, 43.268148, 14.420671], // in the sea near Italy
		];
	}

	/**
	 * @return array<array{float, float}>
	 */
	public static function reverseInvalidProvider(): array
	{
		return [
			[0.123, 0.123], // middle of the ocean
		];
	}

	/**
	 * @dataProvider reverseValidProvider
	 */
	public function testReverseValid(
		string $expectedAddress,
		string $expectedPlusCode,
		string $expectedPlusCodeCompound,
		string|null $expectedCountryFlag,
		float $lat,
		float $lon): void
	{
		$coords = new \DJTommek\Coordinates\Coordinates($lat, $lon);
		$response = self::$api->reverse($coords);
		$this->assertInstanceOf(GeocodeResponse::class, $response);
		$this->assertSame($expectedPlusCode, $response->getPlusCode());
		$this->assertSame($expectedPlusCode, $response->getPlusCode(false));
		$this->assertSame($expectedPlusCodeCompound, $response->getPlusCode(true));

		$this->assertSame($expectedAddress, $response->getAddress());
		$expectedAddressWithFlag = trim($expectedCountryFlag . ' ' . $expectedAddress);
		$this->assertSame($expectedCountryFlag, $response->getCountryFlagEmoji());
		$this->assertSame($expectedAddressWithFlag, $response->getAddressWithFlag());
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
