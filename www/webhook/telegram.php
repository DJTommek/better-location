<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Config;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;
use Nette\Utils\Json;

$request = (new \Nette\Http\RequestFactory())->fromGlobals();

if (Config::isTelegramWebhookPassword() === false) {
	http_response_code(500);
	printf('Error: Telegram password in local config is not set.');
} else if ($request->getHeader(TelegramHelper::WEBHOOK_SECRET_TOKEN_HEADER_KEY) !== Config::TELEGRAM_WEBHOOK_PASSWORD) {
	http_response_code(403);
	printf('Error: Secret HTTP token is not valid.');
} else if (empty($input = file_get_contents('php://input'))) {
	http_response_code(400);
	printf('Error: Telegram webhook API data are missing! This page should be requested only from Telegram servers via webhook.');
} else {
	try {
		$updateData = Json::decode($input, Json::FORCE_ARRAY);
		SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_INPUT, $updateData);

		\App\Factory::Database(); // Just check if database connection is valid, otherwise throw Exception and end script now.

		$telegramCustomWrapper = \App\Factory::Telegram();
		$update = new \unreal4u\TelegramAPI\Telegram\Types\Update($updateData);
		$telegramCustomWrapper->getUpdateEvent($update);
		if ($event = $telegramCustomWrapper->getEvent()) {
			$timerName = 'eventHandling';
			\Tracy\Debugger::timer($timerName);
			$telegramCustomWrapper->handle();
			\Tracy\Debugger::log(sprintf(
				'Handling event %s took %F seconds. Log ID = %d',
				$event::class,
				\Tracy\Debugger::timer($timerName),
				LOG_ID
			), \Tracy\Debugger::DEBUG);
		} else {
			printf('<p>%s</p>', $telegramCustomWrapper->getEventNote());
		}
		printf('OK.');
	} catch (\Throwable $exception) {
		if ($request->getQuery('exception') === '0') {
			printf('Error: "%s".', $exception->getMessage());
		} else {
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $exception;
		}
	}
}
printf('End.');
