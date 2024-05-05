<?php declare(strict_types=1);

use App\BetterLocation\StaticMapProxy;
use App\BetterLocation\StaticMapProxyFactory;
use App\Config;
use App\Database;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
	$services = $container->services()
		->defaults()
		->public()
		->autowire();

	$services->load('App\\Repository\\', __DIR__ . '/libs/Repository/*Repository.php');
	$services->set(StaticMapProxy::class);
	$services->set(TelegramCustomWrapper::class);
	$services->set(StaticMapProxyFactory::class);

	$services->set(Database::class)
		->arg('$server', Config::DB_SERVER)
		->arg('$schema', Config::DB_NAME)
		->arg('$user', Config::DB_USER)
		->arg('$pass', Config::DB_PASS);

	$services->set(\App\BetterLocation\GooglePlaceApi::class)
		->arg('$apiKey', Config::GOOGLE_PLACE_API_KEY);
};
