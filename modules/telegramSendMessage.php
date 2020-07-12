<?php
declare(strict_types=1);

use unreal4u\TelegramAPI\Telegram\Methods\SendMessage;

require_once __DIR__ . '/../src/config.php';

if (!isset($_GET['telegramChatId']) || !is_numeric($_GET['telegramChatId'])) {
	die('Missing or invalid GET parameter "telegramChatId".');
}

$chatId = $_GET['telegramChatId'];

$tg = Factory::Telegram();
$message = new SendMessage();
$message->text = 'Testing message from <a href="https://github.com/DJTommek/php-template">DJTommek/php-template</a>';
$message->chat_id = $chatId;
$message->parse_mode = 'HTML';
$promise = $tg->run($message);
$promise->then(
	function ($response) {
		echo '<p>Message was successfully sended. Check your Telegram chat.</p>';
	},
	function (\Exception $exception) {
		echo '<p>Error while sending telegram message. Check logs for more info.</p>';
		dump($exception);
	}
);
