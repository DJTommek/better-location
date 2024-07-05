<?php declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;

/**
 * @deprecated All services here should be registered in \App\Container and loaded from there.
 */
class Factory
{
	/**
	 * @var array<string,object>
	 */
	private static array $objects = [];
	private static ContainerInterface $container;

	public static function getContainer(): ContainerInterface
	{
		if (!isset(self::$container)) {
			self::$container = self::$container = new Container();
			self::$container->register();
		}

		return self::$container;
	}

	public static function database(): Database
	{
		return self::getContainer()->get(Database::class);
	}

	public static function whatThreeWordsHelper(): ?\App\WhatThreeWord\Helper
	{
		$container = self::getContainer();
		if ($container->has(\App\WhatThreeWord\Helper::class) === false) {
			return null;
		}

		return $container->get(\App\WhatThreeWord\Helper::class);
	}

	public static function geocaching(): \App\Geocaching\Client
	{
		return self::getContainer()->get(\App\Geocaching\Client::class);
	}

	public static function ingressLanchedRu(): \App\IngressLanchedRu\Client
	{
		return self::getContainer()->get(\App\IngressLanchedRu\Client::class);
	}

	/** Not cached */
	public static function bingStaticMaps(): \App\BingMaps\StaticMaps
	{
		return new \App\BingMaps\StaticMaps(Config::BING_STATIC_MAPS_TOKEN);
	}

	public static function servicesManager(): \App\BetterLocation\ServicesManager
	{
		return self::getContainer()->get(\App\BetterLocation\ServicesManager::class);
	}

	public static function requestor(): \App\Utils\Requestor
	{
		return self::getContainer()->get(\App\Utils\Requestor::class);
	}

	public static function httpClient(): ClientInterface
	{
		return self::getContainer()->get(ClientInterface::class);
	}

	public static function nominatim(): Nominatim\NominatimWrapper
	{
		return self::getContainer()->get(Nominatim\NominatimWrapper::class);
	}

	/**
	 * Storing for short-term (it will be cleared on each deploy on production)
	 */
	private static function tempCacheStorage(): \Nette\Caching\Storage
	{
		if (!isset(self::$objects[__METHOD__])) {
			$dir = Config::FOLDER_TEMP . '/nette-cache';
			\Nette\Utils\FileSystem::createDir($dir);
			self::$objects[__METHOD__] = new \Nette\Caching\Storages\FileStorage($dir);
		}
		return self::$objects[__METHOD__];
	}

	/**
	 * Storing data for longer-term (it will NOT be cleared on each deploy on production)
	 */
	private static function permaCacheStorage(): \Nette\Caching\Storage
	{
		if (!isset(self::$objects[__METHOD__])) {
			$dir = Config::FOLDER_DATA . '/nette-perma-cache';
			\Nette\Utils\FileSystem::createDir($dir);
			self::$objects[__METHOD__] = new \Nette\Caching\Storages\FileStorage($dir);
		}
		return self::$objects[__METHOD__];
	}

	public static function cache(string $namespace): \Nette\Caching\Cache
	{
		assert(in_array($namespace, Config::CACHE_NAMESPACES, true));
		return new \Nette\Caching\Cache(self::tempCacheStorage(), $namespace);
	}

	public static function permaCache(string $namespace): \Nette\Caching\Cache
	{
		assert(in_array($namespace, Config::CACHE_NAMESPACES, true));
		return new \Nette\Caching\Cache(self::permaCacheStorage(), $namespace);
	}

	public static function geonames(): \App\Geonames\Geonames
	{
		return self::getContainer()->get(\App\Geonames\Geonames::class);
	}

	public static function openElevation(): \App\OpenElevation\OpenElevation
	{
		return self::getContainer()->get(\App\OpenElevation\OpenElevation::class);
	}

	public static function googleGeocodingApi(): \App\Google\Geocoding\StaticApi
	{
		return self::getContainer()->get(\App\Google\Geocoding\StaticApi::class);
	}

	public static function googleStreetViewApi(): \App\Google\StreetView\StaticApi
	{
		return self::getContainer()->get(\App\Google\StreetView\StaticApi::class);
	}

	public static function staticMapProxyFactory(): \App\BetterLocation\StaticMapProxyFactory
	{
		return self::getContainer()->get(\App\BetterLocation\StaticMapProxyFactory::class);
	}

}
