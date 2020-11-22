<?php declare(strict_types=1);

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use function Clue\React\Block\await;

require_once __DIR__ . '/src/bootstrap.php';

function printlog(string $text)
{
	printf('<p><b>%s</b>: %s</p>', (new DateTime())->format(DATE_W3C), $text);
	\App\Utils\DummyLogger::log(\App\Utils\DummyLogger::NAME_CRON_AUTOREFRESH, $text);
}

$loop = \React\EventLoop\Factory::create();
$tgLog = new \unreal4u\TelegramAPI\TgLog(Config::TELEGRAM_BOT_TOKEN, new \unreal4u\TelegramAPI\HttpClientRequestHandler($loop));

$messagesToRefresh = \App\TelegramUpdateDb::loadAll(
	\App\TelegramUpdateDb::STATUS_ENABLED,
	null,
	Config::REFRESH_CRON_MAX_UPDATES,
	(new DateTime())->sub(new DateInterval(sprintf('PT%dS', Config::REFRESH_CRON_MIN_OLD)))
);

if (count($messagesToRefresh) === 0) {
	printlog('No message need refresh');
} else {
	printlog(sprintf('Loaded %s updates to refresh.', count($messagesToRefresh)));
	$telegramCustomWrapper = new \App\TelegramCustomWrapper\TelegramCustomWrapper(Config::TELEGRAM_BOT_TOKEN, Config::TELEGRAM_BOT_NAME);
	foreach ($messagesToRefresh as $messageToRefresh) {
		try {
			$telegramCustomWrapper->getUpdateEvent($messageToRefresh->getOriginalUpdateObject());
			$event = $telegramCustomWrapper->getEvent();
			$diff = time() - $messageToRefresh->getLastUpdate()->getTimestamp();
			printlog(sprintf('Processing chat ID %d - message ID %d with last refresh %s (%s ago)',
				$messageToRefresh->getChatId(),
				$messageToRefresh->getBotReplyMessageId(),
				$messageToRefresh->getLastUpdate()->format(DATE_W3C),
				App\Utils\General::sToHuman($diff),
			));
			$collection = $event->getCollection();
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
				await($tgLog->performApiRequest($msg), $loop);
			}
			$messageToRefresh->touchLastUpdate();
			printlog(sprintf('Chat ID %d - message ID %d was processed.', $messageToRefresh->getChatId(), $messageToRefresh->getBotReplyMessageId()));
		} catch (\Throwable $exception) {
			printlog(sprintf('Exception occured while processing chat ID %d - message ID %d: %s',
				$messageToRefresh->getChatId(),
				$messageToRefresh->getBotReplyMessageId(),
				$exception->getMessage(),
			));
			\Tracy\Debugger::log($exception, \Tracy\ILogger::EXCEPTION);
		}
	}
	printlog('All updates were processed');
}
