<?php
declare(strict_types=1);

use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use function Clue\React\Block\await;

require_once __DIR__ . '/src/bootstrap.php';
//\Tracy\Debugger::$showBar = false;

$response = new \stdClass();
$response->datetime = (new \DateTimeImmutable())->format(DateTimeInterface::W3C);
$response->result = [];
$response->error = true;
$response->message = null;

try {
	$loop = \React\EventLoop\Factory::create();
	$tgLog = new \unreal4u\TelegramAPI\TgLog(Config::TELEGRAM_BOT_TOKEN, new \unreal4u\TelegramAPI\HttpClientRequestHandler($loop));

	$messagesToRefresh = \App\TelegramUpdateDb::loadAll(\App\TelegramUpdateDb::STATUS_ENABLED);
	if (count($messagesToRefresh) === 0) {
		printf('No message need refresh');
	} else {
		foreach ($messagesToRefresh as $messageToRefresh) {
			$collection = BetterLocationCollection::fromTelegramMessage(
				$messageToRefresh->getOriginalUpdateObject()->message->text,
				$messageToRefresh->getOriginalUpdateObject()->message->entities,
			);
			$processedCollection = new ProcessedMessageResult($collection);
			$processedCollection->setAutorefresh(true);
			$processedCollection->process();
			$text = TelegramHelper::MESSAGE_PREFIX . $processedCollection->getText();
			$text .= sprintf('%s Autorefreshed: %s', Icons::REFRESH, (new \DateTimeImmutable())->format(Config::DATETIME_FORMAT_ZONE));
			if ($collection->count() > 0) {
				$msg = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
				$msg->text = $text;
				$msg->chat_id = $messageToRefresh->getChatId();
				$msg->message_id = $messageToRefresh->getBotReplyMessageId();
				$msg->parse_mode = 'HTML';
				$msg->reply_markup = $processedCollection->getMarkup(1);
				$msg->disable_web_page_preview = true;
				dump($msg);
				$response = await($tgLog->performApiRequest($msg), $loop);
				dump($response);
			}
			$messageToRefresh->touchLastUpdate();
		}
		$response->error = false;
		$response->result[] = 'aaa';
		$response->message = 'bbbb';
	}
} catch (\Exception $exception) {
	$response->error = true;
	$response->message = sprintf('%s Error occured while processing Glympse CRON: %s', Icons::ERROR, $exception->getMessage());
	throw $exception;
}
//die(json_encode($response));
