<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$tg = Factory::Telegram();
$setWebhook = new \unreal4u\TelegramAPI\Telegram\Methods\SetWebhook();

// set webhook url NOT to this file (telegramSetWebhook.php), but to telegramWebhook.php)
$setWebhook->url = sprintf('%s://%s%s/%s',
	$_SERVER['REQUEST_SCHEME'],
	$_SERVER['HTTP_HOST'],
	dirname($_SERVER['REQUEST_URI']),
	str_replace('Set', '', basename($_SERVER['REQUEST_URI']))
);

printf('<p>Setting webhook url to "<a href="%1$s" target="_blank">%1$s</a>"...</p>', $setWebhook->url);

$promise = $tg->run($setWebhook);
$promise->then(
	function ($response) {
		echo '<p>Webhook successfully set! Check your Telegram chat.</p>';
		dump($response);
	},
	function (\Exception $exception) {
		echo '<p>Error while setting webhook. Check logs for more info.</p>';
		dump($exception);
	}
);

