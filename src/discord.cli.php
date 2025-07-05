<?php declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (\App\Config::isDiscord() === false) {
	throw new \RuntimeException('Discord is not configured.');
}

assert(isset($container) && $container instanceof \Psr\Container\ContainerInterface);
$container->get(\App\DiscordCustomWrapper\DiscordCustomWrapper::class)->run();
