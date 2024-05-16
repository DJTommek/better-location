<?php declare(strict_types=1);

namespace Tests\Geonames;

use App\Config;
use App\Geonames\Geonames;
use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Tests\TestUtils;

final class GeonamesTest extends TestCase
{
	public static function basicDataProvider(): array
	{
		return [
			[50.087451, 14.420671, 'Europe/Prague', __DIR__ . '/fixtures/prague.json'],
			[40.7113025, -74.0091831, 'America/New_York', __DIR__ . '/fixtures/new_york.json'],
			[35.6931492, 51.3226672, 'Asia/Tehran', __DIR__ . '/fixtures/tehran.json'],
		];
	}

	/**
	 * @dataProvider basicDataProvider
	 */
	public function testRequest(
		float $expectedLat,
		float $expectedLon,
		string $expectedTimezoneId,
		string $mockedJsonFile,
	): void {
		[$httpClient, $mockHandler] = TestUtils::createMockedClientInterface();
		assert($httpClient instanceof \GuzzleHttp\Client);
		assert($mockHandler instanceof \GuzzleHttp\Handler\MockHandler);
		$cache = TestUtils::getDevNullCache();
		$mockHandler->append(new \GuzzleHttp\Psr7\Response(200, body: file_get_contents($mockedJsonFile)));

		$geonamesApi = new Geonames($httpClient, $cache, 'Dummy');

		$timezone = $geonamesApi->timezone(12, 34);

		$this->assertSame($expectedLat, $timezone->lat);
		$this->assertSame($expectedLon, $timezone->lng);
		$this->assertSame($expectedTimezoneId, $timezone->timezoneId);
	}

	/**
	 * @group request
	 * @dataProvider basicDataProvider
	 */
	public function testRequestReal(
		float $lat,
		float $lon,
		string $expectedTimezoneId,
	): void {
		$httpClient = new Client();
		$cache = TestUtils::getDevNullCache();

		$geonamesApi = new Geonames($httpClient, $cache, Config::GEONAMES_USERNAME);

		$timezone = $geonamesApi->timezone($lat, $lon);

		$this->assertSame($lat, $timezone->lat);
		$this->assertSame($lon, $timezone->lng);
		$this->assertSame($expectedTimezoneId, $timezone->timezoneId);
	}
}
