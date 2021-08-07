<?php declare(strict_types=1);

require_once __DIR__ . '/../../src/bootstrap.php';

use App\Config;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use \App\Utils\SimpleLogger;

if (Config::isTelegramWebhookPassword() === false) {
	printf('Error: Telegram password in local config is not set.');
} else if (isset($_GET['password']) === false || $_GET['password'] !== Config::TELEGRAM_WEBHOOK_PASSWORD) {
	printf('Error: Provided password is not valid.');
} else if (empty($input = file_get_contents('php://input'))) {
	printf('Error: Telegram webhook API data are missing! This page should be requested only from Telegram servers via webhook.');
} else {
	try {
		$updateData = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
		SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_INPUT, $updateData);

		\App\Factory::Database(); // Just check if database connection is valid, otherwise throw Exception and end script now.

		$telegramCustomWrapper = \App\Factory::Telegram();
		$update = new \unreal4u\TelegramAPI\Telegram\Types\Update($updateData);
		$telegramCustomWrapper->getUpdateEvent($update);
		if ($event = $telegramCustomWrapper->getEvent()) {
			$telegramCustomWrapper->handle();
		} else {
			printf('<p>%s</p>', $telegramCustomWrapper->getEventNote());
		}
		printf('OK.');
	} catch (\Throwable $exception) {
		if (isset($_GET['exception']) && $_GET['exception'] === '0') {
			printf('Error: "%s".', $exception->getMessage());
		} else {
			/** @noinspection PhpUnhandledExceptionInspection */
			throw $exception;
		}
	}
}
printf('End.');
