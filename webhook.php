<?php
declare(strict_types=1);

require_once __DIR__ . '/src/config.php';

$updateData = json_decode(file_get_contents('php://input'), true);
$telegramCustomWrapper = new \TelegramCustomWrapper\TelegramCustomWrapper(TELEGRAM_BOT_TOKEN, TELEGRAM_BOT_NAME);
$telegramCustomWrapper->handleUpdate($updateData);
