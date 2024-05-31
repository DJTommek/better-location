<?php declare(strict_types=1);

namespace Tests;

use App\Cache\NetteCachePsr16;
use DJTommek\Coordinates\Coordinates;
use GuzzleHttp\Handler\MockHandler;
use Nette\Caching\Storages\DevNullStorage;

final class TestUtils
{
	public static function randomLat(): float
	{
		return random_int(-89_999_999, 89_999_999) / 1_000_000;
	}

	public static function randomLon(): float
	{
		return random_int(-179_999_999, 179_999_999) / 1_000_000;
	}

	public static function randomCoords(): Coordinates
	{
		return new Coordinates(
			self::randomLat(),
			self::randomLon(),
		);
	}

	/**
	 * @return array{\GuzzleHttp\Client, MockHandler}
	 */
	public static function createMockedHttpClient(): array
	{
		$mockHandler = new \GuzzleHttp\Handler\MockHandler();
		$handlerStack = \GuzzleHttp\HandlerStack::create($mockHandler);
		$httpClient = new \GuzzleHttp\Client([
			'handler' => $handlerStack,
		]);

		return [$httpClient, $mockHandler];
	}

	public static function createDevNullCache(): \Psr\SimpleCache\CacheInterface
	{
		$storage = new DevNullStorage();
		return new NetteCachePsr16($storage);
	}
}
