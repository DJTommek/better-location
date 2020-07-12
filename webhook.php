<?php
declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

$input = file_get_contents('php://input');
$updateData = json_decode($input, true);
\Tracy\Debugger::log('TG Input: ' . $input, \Tracy\ILogger::DEBUG);

$telegramCustomWrapper = new \TelegramCustomWrapper\TelegramCustomWrapper(TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_NAME);
$telegramCustomWrapper->handleUpdate($updateData);
echo 'ok';