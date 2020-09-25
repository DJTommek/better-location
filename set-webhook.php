<?php declare(strict_types=1);

use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\TgLog;

require_once __DIR__ . '/src/bootstrap.php';

printf('<p>Go back to <a href="./index.php">index.php</a></p>');

if (defined('TELEGRAM_WEBHOOK_URL')) {

	$loop = \React\EventLoop\Factory::create();
	$tgLog = new TgLog(TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

	$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();
	$setWebhook->url = TELEGRAM_WEBHOOK_URL;

	$promise = $tgLog->performApiRequest($setWebhook);

	$promise->then(
		function () {
			printf('<h1>Success</h1><p>Telegram webhook URL successfully set to <a href="%1$s" target="_blank">%1$s</a></p>.', TELEGRAM_WEBHOOK_URL);
		},
		function (\Exception $exception) {
			printf('<h1>Error</h1><p>Failed to set Telegram webhook URL to <a href="%1$s" target="_blank">%1$s</a>:<br><b>%2$s</b></p>.', TELEGRAM_WEBHOOK_URL, $exception->getMessage());
			Debugger::log($exception, ILogger::EXCEPTION);
		}
	);
	$loop->run();
} else {
	printf('Updating Telegram webhook URL is not allowed. Set "TELEGRAM_WEBHOOK_URL" to some URL and try again.');
}

