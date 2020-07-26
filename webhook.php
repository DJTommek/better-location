<?php
declare(strict_types=1);

use \Utils\DummyLogger;

require_once __DIR__ . '/src/config.php';

printf('<p>Go back to <a href="./index.php">index.php</a></p>');

$input = file_get_contents('php://input');
$updateData = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
DummyLogger::log(DummyLogger::NAME_TELEGRAM_INPUT, $updateData);

try {
	$telegramCustomWrapper = new \TelegramCustomWrapper\TelegramCustomWrapper(TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_NAME);
	$telegramCustomWrapper->handleUpdate($updateData);
	printf('OK.');
} catch (\Exception $exception) {
	printf('Error: "%s".', $exception->getMessage());
}
