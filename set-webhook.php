<?php declare(strict_types=1);

use App\Config;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\TgLog;
use function Clue\React\Block\await;

require_once __DIR__ . '/src/bootstrap.php';

printf('<p>Go back to <a href="./index.php">index.php</a></p>');

if (\App\Dashboard\Status::isTGWebhookUrSet()) {
	$loop = \React\EventLoop\Factory::create();
	$tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

	$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();
	$setWebhook->url = Config::TELEGRAM_WEBHOOK_URL;
	try {
		await($tgLog->performApiRequest($setWebhook), $loop);
		printf('<h1>Success</h1><p>Telegram webhook URL successfully set to <a href="%1$s" target="_blank">%1$s</a></p>.', Config::TELEGRAM_WEBHOOK_URL);
	} catch (ClientException $exception) {
		printf('<h1>Error</h1><p>Failed to set Telegram webhook URL to <a href="%1$s" target="_blank">%1$s</a>:<br><b>%2$s</b></p>.', Config::TELEGRAM_WEBHOOK_URL, $exception->getMessage());
		Debugger::log($exception, ILogger::EXCEPTION);
	}
} else {
	printf('Updating Telegram webhook URL is not allowed. Set "TELEGRAM_WEBHOOK_URL" to some URL and try again.');
}

