<?php
declare(strict_types=1);

require_once __DIR__ . '/../src/config.php';

$tg = Factory::Telegram();

$updateData = json_decode(file_get_contents('php://input'), true);
$update = new \unreal4u\TelegramAPI\Telegram\Types\Update($updateData);

if ($update->update_id === 0) {
	die('Telegram webhook API data are missing! This page should be requested only from Telegram servers via webhook.');
}

$message = new \unreal4u\TelegramAPI\Telegram\Methods\SendMessage();
$message->text = 'Testing response message from <a href="https://github.com/DJTommek/php-template">DJTommek/php-template</a>';
$message->chat_id = $update->message->chat->id;
$message->parse_mode = 'HTML';
$promise = $tg->run($message);
