<?php declare(strict_types=1);

namespace App;

class Factory
{
	/**
	 * @var array<string,object>
	 */
	private static array $objects = [];

	public static function database(): Database
	{
		if (!isset(self::$objects['database'])) {
			self::$objects['database'] = new Database(Config::DB_SERVER, Config::DB_NAME, Config::DB_USER, Config::DB_PASS);
		}
		return self::$objects['database'];
	}

	public static function telegram(): \App\TelegramCustomWrapper\TelegramCustomWrapper
	{
		if (!isset(self::$objects['telegram'])) {
			self::$objects['telegram'] = new \App\TelegramCustomWrapper\TelegramCustomWrapper();
		}
		return self::$objects['telegram'];
	}

	public static function whatThreeWords(): \What3words\Geocoder\Geocoder
	{
		if (!isset(self::$objects['w3w'])) {
			self::$objects['w3w'] = new \What3words\Geocoder\Geocoder(Config::W3W_API_KEY);
		}
		return self::$objects['w3w'];
	}

	public static function glympse(): \DJTommek\GlympseApi\GlympseApi
	{
		if (!isset(self::$objects['glympse'])) {
			$client = new \DJTommek\GlympseApi\GlympseApi(Config::GLYMPSE_API_KEY);
			$client->setUsername(Config::GLYMPSE_API_USERNAME);
			$client->setPassword(Config::GLYMPSE_API_PASSWORD);
			$accessToken = $client->accountLogin();
			$client->setAccessToken($accessToken);
			self::$objects['glympse'] = $client;
		}
		return self::$objects['glympse'];
	}

	public static function geocaching(): \App\Geocaching\Client
	{
		if (!isset(self::$objects['geocaching'])) {
			self::$objects['geocaching'] = new \App\Geocaching\Client(Config::GEOCACHING_COOKIE);
			self::$objects['geocaching']->setCache(Config::CACHE_TTL_GEOCACHING_API);
		}
		return self::$objects['geocaching'];
	}

	public static function foursquare(): \App\Foursquare\Client
	{
		if (!isset(self::$objects['foursquare'])) {
			self::$objects['foursquare'] = new \App\Foursquare\Client(Config::FOURSQUARE_CLIENT_ID, Config::FOURSQUARE_CLIENT_SECRET);
			self::$objects['foursquare']->setCache(Config::CACHE_TTL_FOURSQUARE_API);
		}
		return self::$objects['foursquare'];
	}

	public static function ingressLanchedRu(): \App\IngressLanchedRu\Client
	{
		if (!isset(self::$objects['ingressLanchedRu'])) {
			self::$objects['ingressLanchedRu'] = new \App\IngressLanchedRu\Client();
			self::$objects['ingressLanchedRu']->setCache(Config::CACHE_TTL_INGRESS_LANCHED_RU_API);
		}
		return self::$objects['ingressLanchedRu'];
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
		if (!isset(self::$objects['servicesManager'])) {
			self::$objects['servicesManager'] = new \App\BetterLocation\ServicesManager();
		}
		return self::$objects['servicesManager'];
	}

	public static function latte(string $template = null, $params = []): \Latte\Engine
	{
		$latte = new \Latte\Engine();
		$latte->setTempDirectory(Config::FOLDER_TEMP . '/latte');
		if ($template !== null) {
			$latte->render(Config::FOLDER_TEMPLATES . '/' . $template, $params);
		}
		return $latte;
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
		if (!isset(self::$objects[__METHOD__])) {
			self::$objects[__METHOD__] = new \App\Google\Geocoding\StaticApi(Config::GOOGLE_PLACE_API_KEY);
		}
		return self::$objects[__METHOD__];
	}

	public static function googleStreetViewApi(): \App\Google\StreetView\StaticApi
	{
		if (!isset(self::$objects[__METHOD__])) {
			self::$objects[__METHOD__] = new \App\Google\StreetView\StaticApi(Config::GOOGLE_PLACE_API_KEY);
		}
		return self::$objects[__METHOD__];
	}

	public static function googlePlaceApi(): \App\BetterLocation\GooglePlaceApi
	{
		if (!isset(self::$objects[__METHOD__])) {
			self::$objects[__METHOD__] = new \App\BetterLocation\GooglePlaceApi(Config::GOOGLE_PLACE_API_KEY);
		}
		return self::$objects[__METHOD__];
	}
}
