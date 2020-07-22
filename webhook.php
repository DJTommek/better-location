<?php
declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

printf('<p>Go back to <a href="./index.php">index.php</a></p>');

$input = file_get_contents('php://input');
$updateData = json_decode($input, true);
\Tracy\Debugger::log('TG Input: ' . $input, \Tracy\ILogger::DEBUG);

try {
	$telegramCustomWrapper = new \TelegramCustomWrapper\TelegramCustomWrapper(TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_NAME);
	$telegramCustomWrapper->handleUpdate($updateData);
	printf('OK.');
} catch (\Exception $exception) {
	printf('Error: "%s".', $exception->getMessage());
}
