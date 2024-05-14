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
	$services->load('App\\Web\\', __DIR__ . '/libs/Web/*/*Presenter.php');

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
		->tag($tagTgEvents);

	$services->set(StaticMapProxy::class);
	$services->set(\App\BetterLocation\FromTelegramMessage::class);
	$services->set(TelegramCustomWrapper::class);
	$services->set(CustomTelegramLogger::class);
	$services->set(StaticMapProxyFactory::class);
	$services->set(\App\BetterLocation\ServicesManager::class);

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

	$services->set(TelegramEventFactory::class)
		->arg('$events', tagged_iterator($tagTgEvents));

	$services->set(\Latte\Engine::class);
	$services->set(\App\Factory\LatteFactory::class);
	$services->set(\App\Web\Login\LoginFacade::class)
		->share(false);

	$services->set(\Nette\Http\RequestFactory::class);
	$services->set(\Nette\Http\Request::class)
		->factory([service(\Nette\Http\RequestFactory::class), 'fromGlobals']);

	if (Config::isGlympse()) {
		$services->set(\App\Factory\GlympseApiFactory::class)
			->arg('$apiKey', Config::GLYMPSE_API_KEY)
			->arg('$username', Config::GLYMPSE_API_USERNAME)
			->arg('$password', Config::GLYMPSE_API_PASSWORD);

		$services->set(\DJTommek\GlympseApi\GlympseApi::class)
			->factory([service(\App\Factory\GlympseApiFactory::class), 'create']);
	}

	if (Config::isGeocaching()) {
		$services->set(\App\Factory\GeocachingApiFactory::class)
			->arg('$cookie', Config::GEOCACHING_COOKIE)
			->arg('$cacheTtl', Config::CACHE_TTL_GEOCACHING_API);

		$services->set(\App\Geocaching\Client::class)
			->factory([service(\App\Factory\GeocachingApiFactory::class), 'create']);
	}

	if (Config::isW3W()) {
		$services->set(\What3words\Geocoder\Geocoder::class)
			->arg('$api_key', Config::W3W_API_KEY);

		$services->set(\App\WhatThreeWord\Helper::class);
	}

	if (Config::isFoursquare()) {
		$services->set(\App\Factory\FoursquareApiFactory::class)
			->arg('$clientId', Config::FOURSQUARE_CLIENT_ID)
			->arg('$clientSecret', Config::FOURSQUARE_CLIENT_SECRET)
			->arg('$cacheTtl', Config::CACHE_TTL_FOURSQUARE_API);

		$services->set(App\Foursquare\Client::class)
			->factory([service(\App\Factory\FoursquareApiFactory::class), 'create']);
	}

	$services->set(\App\Factory\IngressLanchedRuFactory::class)
		->arg('$cacheTtl', Config::CACHE_TTL_INGRESS_LANCHED_RU_API);

	$services->set(\App\IngressLanchedRu\Client::class)
		->factory([service(\App\Factory\IngressLanchedRuFactory::class), 'create']);

	$services->set(\App\Utils\Requestor::class);

	// PSR-7 HTTP Client
	$services->set(\App\Factory\HttpClientFactory::class);
	$services->set(\GuzzleHttp\Client::class);
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

	$services->set(\App\Factory\NetteCacheFileStorageFactory::class)
		->arg('$dir', Config::FOLDER_TEMP . '/nette-cache');
};
