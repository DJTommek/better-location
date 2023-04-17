<?php declare(strict_types=1);

namespace Tests\Google\Geocoding;

use App\Config;
use App\Google\Geocoding\StaticApi;
use App\Utils\Coordinates;
use PHPUnit\Framework\TestCase;

/**
 * @group request
 */
final class StaticApiTest extends TestCase
{
	private static StaticApi $api;

	public static function setUpBeforeClass(): void
	{
		if (Config::GOOGLE_PLACE_API_KEY === null) {
			self::markTestSkipped('Missing Google API key');
		}
		self::$api = new StaticApi(Config::GOOGLE_PLACE_API_KEY);
	}

	public function testReverse(): void
	{
		$result = self::$api->reverse(new Coordinates(50.087451, 14.420671));
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('Mikulášská 22, 110 00 Praha 1-Staré Město, Czechia', $result->results[0]->formatted_address);
		$this->assertSame('9F2P3CPC+X7M', $result->plus_code->global_code);
		$this->assertSame('3CPC+X7M Prague, Czechia', $result->plus_code->compound_code);

		$result = self::$api->reverse(new Coordinates(50.023194, 14.732896));
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('2PFM+75 Doubek, Czechia', $result->results[0]->formatted_address);
		$this->assertSame('9F2P2PFM+75C', $result->plus_code->global_code);
		$this->assertSame('2PFM+75C Doubek, Czechia', $result->plus_code->compound_code);

		$result = self::$api->reverse(new Coordinates(-42.7363111, 147.4392722));
		$this->assertInstanceOf(\stdClass::class, $result);
		$this->assertSame('35 Bathurst St, Richmond TAS 7025, Australia', $result->results[0]->formatted_address);
		$this->assertSame('4R997C7Q+FPC', $result->plus_code->global_code);
		$this->assertSame('7C7Q+FPC Richmond TAS, Australia', $result->plus_code->compound_code);

		// No valid address
		$this->assertNull(self::$api->reverse(new Coordinates(0.123, 0.123)));
	}
}
