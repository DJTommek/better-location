<?php declare(strict_types=1);

namespace App;

use Psr\Container\ContainerInterface;

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

	public static function whatThreeWords(): ?\What3words\Geocoder\Geocoder
	{
		$container = self::getContainer();
		if ($container->has(\What3words\Geocoder\Geocoder::class) === false) {
			return null;
		}

		return $container->get(\What3words\Geocoder\Geocoder::class);
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

	public static function ingressMosaic(): \App\IngressMosaic\Client
	{
		if (!isset(self::$objects['ingressMosaic'])) {
			self::$objects['ingressMosaic'] = new \App\IngressMosaic\Client(Config::INGRESS_MOSAIC_COOKIE_XSRF, Config::INGRESS_MOSAIC_COOKIE_SESSION);
			self::$objects['ingressMosaic']->setCache(Config::CACHE_TTL_INGRESS_MOSAIC);
		}
		return self::$objects['ingressMosaic'];
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

	public static function nominatim(): \maxh\Nominatim\Nominatim
	{
		if (!isset(self::$objects['nominatim'])) {
			$headers = [
				'User-Agent' => Config::NOMINATIM_USER_AGENT,
			];
			$client = new \GuzzleHttp\Client([
				'base_uri' => Config::NOMINATIM_URL,
				'timeout' => 5,
				'connection_timeout' => 5,
			]);
			self::$objects['nominatim'] = new \maxh\Nominatim\Nominatim(Config::NOMINATIM_URL, $headers, $client);
		}
		return self::$objects['nominatim'];
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
		if (!isset(self::$objects['geonames'])) {
			self::$objects['geonames'] = new \App\Geonames\Geonames(Config::GEONAMES_USERNAME);
		}
		return self::$objects['geonames'];
	}

	public static function openElevation(): \App\OpenElevation\OpenElevation
	{
		if (!isset(self::$objects['openelevation'])) {
			self::$objects['openelevation'] = new \App\OpenElevation\OpenElevation();
			self::$objects['openelevation']->setCache(Config::CACHE_TTL_OPEN_ELEVATION);
		}
		return self::$objects['openelevation'];
	}

	public static function googleGeocodingApi(): \App\Google\Geocoding\StaticApi
	{
		return self::getContainer()->get(\App\Google\Geocoding\StaticApi::class);
	}

	public static function googleStreetViewApi(): \App\Google\StreetView\StaticApi
	{
		return self::getContainer()->get(\App\Google\StreetView\StaticApi::class);
	}

	public static function googlePlaceApi(): \App\BetterLocation\GooglePlaceApi
	{
		return self::getContainer()->get(\App\BetterLocation\GooglePlaceApi::class);
	}

	public static function staticMapProxyFactory(): \App\BetterLocation\StaticMapProxyFactory
	{
		return self::getContainer()->get(\App\BetterLocation\StaticMapProxyFactory::class);
	}

}
