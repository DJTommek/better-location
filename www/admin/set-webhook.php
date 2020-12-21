<?php declare(strict_types=1);

use App\Config;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\TgLog;
use function Clue\React\Block\await;

require_once __DIR__ . '/../../src/bootstrap.php';

if (empty(\App\Config::ADMIN_PASSWORD)) {
	die('Set ADMIN_PASSWORD in your local config file first');
}

if (isset($_GET['password']) === false || $_GET['password'] !== \App\Config::ADMIN_PASSWORD) {
	die('You don\'t have access without password.');
}

if (Config::isTelegram()) {
	$loop = \React\EventLoop\Factory::create();
	$tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($loop));

	$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();
	$setWebhook->url = Config::getTelegramWebhookUrl(true);
	try {
		await($tgLog->performApiRequest($setWebhook), $loop);
		printf('<h1>Success</h1><p>Telegram webhook URL successfully set to <a href="%1$s" target="_blank">%1$s</a> with secret password.</p>', Config::getTelegramWebhookUrl());
	} catch (ClientException $exception) {
		printf('<h1>Error</h1><p>Failed to set Telegram webhook URL to <a href="%1$s" target="_blank">%1$s</a>:<br><b>%2$s</b></p>.', Config::getTelegramWebhookUrl(), $exception->getMessage());
		Debugger::log($exception, ILogger::EXCEPTION);
	}
} else {
	printf('Updating Telegram webhook URL is not allowed. Set all necessary TELEGRAM_* constants in local config and try again.');
}

