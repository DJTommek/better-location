<?php declare(strict_types=1);

namespace App;

class Factory
{
	private static array $objects = array();

	static function Database(): Database
	{
		if (!isset(self::$objects['database'])) {
			self::$objects['database'] = new Database(Config::DB_SERVER, Config::DB_NAME, Config::DB_USER, Config::DB_PASS);
		}
		return self::$objects['database'];
	}

	static function Telegram(): \App\TelegramCustomWrapper\TelegramCustomWrapper
	{
		if (!isset(self::$objects['telegram'])) {
			self::$objects['telegram'] = new \App\TelegramCustomWrapper\TelegramCustomWrapper();
		}
		return self::$objects['telegram'];
	}

	static function WhatThreeWords(): \What3words\Geocoder\Geocoder
	{
		if (!isset(self::$objects['w3w'])) {
			self::$objects['w3w'] = new \What3words\Geocoder\Geocoder(Config::W3W_API_KEY);
		}
		return self::$objects['w3w'];
	}

	static function Glympse(): \DJTommek\GlympseApi\GlympseApi
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

	static function Geocaching(): \App\Geocaching\Client
	{
		if (!isset(self::$objects['geocaching'])) {
			self::$objects['geocaching'] = new \App\Geocaching\Client(Config::GEOCACHING_COOKIE);
			self::$objects['geocaching']->setCache(Config::CACHE_TTL_GEOCACHING_API);
		}
		return self::$objects['geocaching'];
	}

	static function Foursquare(): \App\Foursquare\Client
	{
		if (!isset(self::$objects['foursquare'])) {
			self::$objects['foursquare'] = new \App\Foursquare\Client(Config::FOURSQUARE_CLIENT_ID, Config::FOURSQUARE_CLIENT_SECRET);
			self::$objects['foursquare']->setCache(Config::CACHE_TTL_FOURSQUARE_API);
		}
		return self::$objects['foursquare'];
	}

	static function IngressLanchedRu(): \App\IngressLanchedRu\Client
	{
		if (!isset(self::$objects['ingressLanchedRu'])) {
			self::$objects['ingressLanchedRu'] = new \App\IngressLanchedRu\Client();
			self::$objects['ingressLanchedRu']->setCache(Config::CACHE_TTL_INGRESS_LANCHED_RU_API);
		}
		return self::$objects['ingressLanchedRu'];
	}

	static function IngressMosaic(): \App\IngressMosaic\Client
	{
		if (!isset(self::$objects['ingressMosaic'])) {
			self::$objects['ingressMosaic'] = new \App\IngressMosaic\Client(Config::INGRESS_MOSAIC_COOKIE_XSRF, Config::INGRESS_MOSAIC_COOKIE_SESSION);
			self::$objects['ingressMosaic']->setCache(Config::CACHE_TTL_INGRESS_MOSAIC);
		}
		return self::$objects['ingressMosaic'];
	}

	/** Not cached */
	static function BingStaticMaps(): \App\BingMaps\StaticMaps
	{
		return new \App\BingMaps\StaticMaps(Config::BING_STATIC_MAPS_TOKEN);
	}

	static function ServicesManager(): \App\BetterLocation\ServicesManager
	{
		if (!isset(self::$objects['servicesManager'])) {
			self::$objects['servicesManager'] = new \App\BetterLocation\ServicesManager();
		}
		return self::$objects['servicesManager'];
	}

	static function Latte(string $template = null, $params = []): \Latte\Engine
	{
		$latte = new \Latte\Engine();
		$latte->setTempDirectory(Config::FOLDER_TEMP . '/latte');
		if ($template !== null) {
			$latte->render(Config::FOLDER_TEMPLATES . '/' . $template, $params);
		}
		return $latte;
	}

	static function Nominatim(): \maxh\Nominatim\Nominatim
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

	private static function CacheStorage(): \Nette\Caching\Storage
	{
		if (!isset(self::$objects['cacheStorage'])) {
			$dir = Config::FOLDER_TEMP . '/nette-cache';
			\Nette\Utils\FileSystem::createDir($dir);
			self::$objects['cacheStorage'] = new \Nette\Caching\Storages\FileStorage($dir);
		}
		return self::$objects['cacheStorage'];
	}

	public static function Cache(string $namespace): \Nette\Caching\Cache
	{
		return new \Nette\Caching\Cache(self::CacheStorage(), $namespace);
	}

	static function Geonames(): \GeoNames\Client
	{
		if (!isset(self::$objects['geonames'])) {
			self::$objects['geonames'] = new \GeoNames\Client(Config::GEONAMES_USERNAME);
		}
		return self::$objects['geonames'];
	}

	public static function OpenElevation(): \App\OpenElevation\OpenElevation
	{
		if (!isset(self::$objects['openelevation'])) {
			self::$objects['openelevation'] = new \App\OpenElevation\OpenElevation();
			self::$objects['openelevation']->setCache(Config::CACHE_TTL_OPEN_ELEVATION);
		}
		return self::$objects['openelevation'];
	}

	public static function GoogleGeocodingApi(): \App\Google\Geocoding\StaticApi
	{
		if (!isset(self::$objects[__METHOD__])) {
			self::$objects[__METHOD__] = new \App\Google\Geocoding\StaticApi(Config::GOOGLE_PLACE_API_KEY);
		}
		return self::$objects[__METHOD__];
	}
}
