<?php declare(strict_types=1);

use App\BetterLocation\StaticMapProxy;
use App\BetterLocation\StaticMapProxyFactory;
use App\Config;
use App\Database;
use App\Logger\CustomTelegramLogger;
use App\TelegramCustomWrapper\Events\EventFactory as TelegramEventFactory;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;
use function Symfony\Component\DependencyInjection\Loader\Configurator\tagged_iterator;

return static function (ContainerConfigurator $container): void {
	$tagTgEvents = 'app.telegram.events';

	$services = $container->services()
		->defaults()
		->public()
		->autowire();

	$services->load('App\\Web\\', __DIR__ . '/libs/Web/*/*Template.php');
	$services->load('App\\Web\\', __DIR__ . '/libs/Web/**/*Presenter.php')
		->call('setDependencies');

	$services->set(\App\Web\Api\v1\InputProcessPresenter::class)
		->call('setDependencies')
		->arg('$apiKeys', Config::API_KEYS);

	$services->load('App\\Repository\\', __DIR__ . '/libs/Repository/*Repository.php');

	// Register all BetterLocation services
	$betterLocationServices = \App\BetterLocation\ServicesManager::services();
	foreach ($betterLocationServices as $betterLocationService) {
		$services->set($betterLocationService)
			->share(false);
	}

	// @TODO {rqd9s3z9i9} fix this to NOT tag classes, that does not inherit from App\TelegramCustomWrapper\Events\Events::class
	$services
		->load('App\\TelegramCustomWrapper\\Events\\', __DIR__ . '/libs/TelegramCustomWrapper/Events/')
		->call('setDependencies')
		->tag($tagTgEvents);

	$services->set(\App\Web\Locations\LocationsTemplate::class)
		->arg('$mapyCzApiKey', Config::MAPY_CZ_TILES_API_KEY);
	$services->set(\App\Web\ChatHistory\ChatHistoryTemplate::class)
		->arg('$mapyCzApiKey', Config::MAPY_CZ_TILES_API_KEY);

	$services->set(StaticMapProxy::class);
	$services->set(\App\BetterLocation\FromTelegramMessage::class);
	$services->set(TelegramCustomWrapper::class);
	$services->set(\App\TelegramCustomWrapper\ChatMemberRecalculator::class);
	$services->set(CustomTelegramLogger::class);
	$services->set(StaticMapProxyFactory::class);
	$services->set(\App\BetterLocation\ServicesManager::class);
	$services->set(\App\BetterLocation\ProcessExample::class);
	$services->set(\App\Factory\ProcessedMessageResultFactory::class);
	$services->set(\App\Factory\ChatFactory::class);
	$services->set(\App\Factory\UserFactory::class);
	$services->set(\App\Maintenance\LogArchiver::class);
	$services->set(\App\BetterLocation\Service\UniversalWebsiteService\LdJsonProcessor::class);
	$services->set(\App\Address\AddressProvider::class);
	$services->set(\App\Address\NullAddressProvider::class);
	$services->set(\App\Address\UniversalAddressProvider::class)
		->arg('$cache', service(\App\Cache\StorageInterface::class));

	// Static map providers
	$staticMapDefaultProvider = null;
	if (Config::isBingStaticMaps()) {
		$services->set(\App\BingMaps\StaticMaps::class)
			->arg('$apiKey', Config::BING_STATIC_MAPS_TOKEN);
		$staticMapDefaultProvider = \App\BingMaps\StaticMaps::class;
	}

	if (Config::isMapBoxStaticMaps()) {
		$services->set(\App\MapBox\StaticMaps::class)
			->arg('$apiKey', Config::MAPBOX_STATIC_MAPS_TOKEN);
		$staticMapDefaultProvider = \App\MapBox\StaticMaps::class;
	}

	if ($staticMapDefaultProvider !== null) {
		// Set interface and default provider
		$services->set(\App\StaticMaps\StaticMapsProviderInterface::class)
			->alias(\App\StaticMaps\StaticMapsProviderInterface::class, $staticMapDefaultProvider);
	}

	$services->set(Database::class)
		->arg('$server', Config::DB_SERVER)
		->arg('$schema', Config::DB_NAME)
		->arg('$user', Config::DB_USER)
		->arg('$pass', Config::DB_PASS);

	if (Config::isGooglePlaceApi()) {
		$services->set(\App\Google\Geocoding\StaticApi::class)
			->arg('$apiKey', Config::GOOGLE_PLACE_API_KEY);
		$services->set(\App\Google\StreetView\StaticApi::class)
			->arg('$apiKey', Config::GOOGLE_PLACE_API_KEY);
		$services->set(\App\BetterLocation\GooglePlaceApi::class)
			->arg('$apiKey', Config::GOOGLE_PLACE_API_KEY);
	}

	$services->set(\App\Address\AddressProvider::class)
		->alias(\App\Address\AddressProvider::class, \App\Address\UniversalAddressProvider::class);


	$services->set(TelegramEventFactory::class)
		->arg('$events', tagged_iterator($tagTgEvents));

	$services->set(\DJTommek\MapyCzApi\MapyCzApi::class);

	$services->set(\Latte\Engine::class);
	$services->set(\App\Factory\LatteFactory::class);
	$services->set(\App\Web\Login\LoginFacade::class)
		->share(false);

	$services->set(\Nette\Http\RequestFactory::class);
	$services->set(\Nette\Http\Request::class)
		->factory([service(\Nette\Http\RequestFactory::class), 'fromGlobals']);

	$services->set(\App\Geonames\Geonames::class)
		->arg('$username', Config::GEONAMES_USERNAME);

	$services->set(\App\OpenElevation\OpenElevation::class)
		->arg('$cacheTtl', Config::CACHE_TTL_OPEN_ELEVATION);

	$services->set(\App\BetterLocation\FavouriteNameGenerator::class);

	if (Config::isGlympse()) {
		$services->set(\App\Factory\GlympseApiFactory::class)
			->arg('$apiKey', Config::GLYMPSE_API_KEY)
			->arg('$username', Config::GLYMPSE_API_USERNAME)
			->arg('$password', Config::GLYMPSE_API_PASSWORD)
			->arg('$accessTokenPath', Config::glympseAccessTokenPath());

		$services->set(\DJTommek\GlympseApi\GlympseApi::class)
			->factory([service(\App\Factory\GlympseApiFactory::class), 'create']);
	}

	if (Config::isGeocaching()) {
		$services->set(\App\Geocaching\Client::class)
			->arg('$cookieToken', Config::GEOCACHING_COOKIE)
			->arg('$cacheTtl', Config::CACHE_TTL_GEOCACHING_API);
	}

	if (Config::isW3W()) {
		$services->set(\What3words\Geocoder\Geocoder::class)
			->arg('$apiKey', Config::W3W_API_KEY);

		$services->set(\App\WhatThreeWord\Helper::class);
	}

	if (Config::isFoursquare()) {
		$services->set(App\Foursquare\Client::class)
			->arg('$clientId', Config::FOURSQUARE_CLIENT_ID)
			->arg('$clientSecret', Config::FOURSQUARE_CLIENT_SECRET)
			->arg('$cacheTtl', Config::CACHE_TTL_FOURSQUARE_API);
	}

	$services->set(\App\IngressLanchedRu\Client::class)
		->arg('$cacheTtl', Config::CACHE_TTL_INGRESS_LANCHED_RU_API);

	$services->set(\App\Factory\NominatimFactory::class)
		->arg('$nominatimUrl', Config::NOMINATIM_URL)
		->arg('$userAgent', Config::NOMINATIM_USER_AGENT);
	$services->set(\App\Nominatim\NominatimWrapper::class);
	$services->set(maxh\Nominatim\Nominatim::class)
		->factory([service(\App\Factory\NominatimFactory::class), 'create']);

	$services->set(\App\Utils\Requestor::class);

	// Guzzle client
	$services->set(\App\Factory\GuzzleClientFactory::class);
	$services->set(\GuzzleHttp\Client::class)
		->factory([service(\App\Factory\GuzzleClientFactory::class), 'create']);

	// PSR-7 HTTP Client
	$services->set(\App\Factory\HttpClientFactory::class);
	$services->set(\Psr\Http\Client\ClientInterface::class)
		->factory([service(\App\Factory\HttpClientFactory::class), 'create']);

	// PSR-16 Cache (default is Nette cache via custom PSR 16 adapter)
	$services->set(\App\Cache\NetteCachePsr16::class);
	$services->set(\Psr\SimpleCache\CacheInterface::class)
		->alias(\Psr\SimpleCache\CacheInterface::class, \App\Cache\NetteCachePsr16::class);

	// Register and configure some of Nette Cache storages (default is FileStorage)
	$services->set(\Nette\Caching\Storages\DevNullStorage::class);
	$services->set(\Nette\Caching\Storages\MemoryStorage::class);
	$services->set(\Nette\Caching\Storages\FileStorage::class)
		->factory([service(\App\Factory\NetteCacheFileStorageFactory::class), 'create'])
		->alias(\Nette\Caching\Storage::class, \Nette\Caching\Storages\FileStorage::class);

	$tempCachePath = Config::FOLDER_TEMP . '/nette-cache';
	$permaCachePath = Config::FOLDER_DATA . '/perma-cache';

	$services->set(\App\Factory\NetteCacheFileStorageFactory::class)
		->arg('$dir', $tempCachePath);

	$services->set(\App\Factory\TempCacheFactory::class)
		->arg('$dir', $tempCachePath);

	$services->set(\App\Cache\StorageInterface::class)
		->factory([service(\App\Factory\PermaCacheFactory::class), 'create']);
	$services->set(\App\Factory\PermaCacheFactory::class)
		->arg('$dir', $permaCachePath);
};
