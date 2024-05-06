<?php declare(strict_types=1);

use App\BetterLocation\StaticMapProxy;
use App\BetterLocation\StaticMapProxyFactory;
use App\Config;
use App\Database;
use App\Logger\CustomTelegramLogger;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$services = $container->services()
		->defaults()
		->public()
		->autowire();

	$services->load('App\\Web\\', __DIR__ . '/libs/Web/*/*Template.php');
	$services->load('App\\Web\\', __DIR__ . '/libs/Web/*/*Presenter.php');

	$services->load('App\\Repository\\', __DIR__ . '/libs/Repository/*Repository.php');

	$services->load('App\\TelegramCustomWrapper\\Events\\', __DIR__ . '/libs/TelegramCustomWrapper/Events/*');

	$services->set(StaticMapProxy::class);
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

	$services->set(\What3words\Geocoder\Geocoder::class)
		->arg('$api_key', Config::W3W_API_KEY);
};
