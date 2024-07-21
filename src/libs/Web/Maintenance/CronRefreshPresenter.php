<?php declare(strict_types=1);

namespace App\Web\Maintenance;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Factory\ProcessedMessageResultFactory;
use App\Icons;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Formatter;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Telegram;

class CronRefreshPresenter extends MainPresenter
{
	/**
	 * @var list<string>
	 */
	private array $log;

	public function __construct(
		private readonly TelegramCustomWrapper $telegramCustomWrapper,
		private readonly ProcessedMessageResultFactory $processedMessageResultFactory,
	) {
	}

	public function action(): void
	{
		$this->log = [];

		if ($this->request->getQuery('password') !== \App\Config::CRON_PASSWORD) {
			$this->apiResponse(true, 'Invalid password', httpCode: self::HTTP_FORBIDDEN);
		}

		$this->run2();

		$result = new \stdClass();
		$result->messages = $this->log;

		$this->apiResponse(false, 'Done', $result);
	}

	private function run2(): void
	{
		$now = new \DateTimeImmutable();

		$messagesToRefresh = \App\TelegramUpdateDb::loadAll(
			\App\TelegramUpdateDb::STATUS_ENABLED,
			null,
			Config::REFRESH_CRON_MAX_UPDATES,
			$now->sub(new \DateInterval(sprintf('PT%dS', Config::REFRESH_CRON_MIN_OLD))),
		);

		if (count($messagesToRefresh) === 0) {
			$this->printlog('No message need refresh');
			return;
		}

		$this->printlog(sprintf('Loaded %s updates to refresh.', count($messagesToRefresh)));
		foreach ($messagesToRefresh as $messageToRefresh) {
			$id = sprintf('%d-%d', $messageToRefresh->telegramChatId, $messageToRefresh->messageIdToEdit);
			try {
				$event = $this->telegramCustomWrapper->analyze($messageToRefresh->originalUpdateObject);
				$this->printlog(sprintf('Processing %s with last refresh at %s (%s ago)',
					$id,
					$messageToRefresh->getLastUpdate()->format(DATE_W3C),
					Formatter::ago($messageToRefresh->getLastUpdate()),
				));
				$collection = $event->getCollection();

				if ($collection === null) {
					$errorMessage = sprintf(
						'Event "%s" does not have support for processing collections, skipping update %s.',
						$event::class,
						$id,
					);
					$this->printlog($errorMessage);
					\Tracy\Debugger::log($errorMessage, \Tracy\Debugger::WARNING);
					continue;
				}

				$processedCollection = $this->processedMessageResultFactory->create(
					collection: $collection,
					messageSettings: $event->getMessageSettings(),
					pluginer: $event->getPluginer(),
				);
				$processedCollection->setAutorefresh(true);
				$processedCollection->process();

				$msg = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
				$msg->chat_id = $messageToRefresh->telegramChatId;
				$msg->message_id = $messageToRefresh->messageIdToEdit;
				$msg->parse_mode = 'HTML';
				$msg->disable_web_page_preview = true;

				// remove last row where are located autorefresh buttons and replace this row with disabled state
				$lastAutorefreshMarkup = $messageToRefresh->getLastResponseReplyMarkup();
				if ($lastAutorefreshMarkup) {
					array_pop($lastAutorefreshMarkup->inline_keyboard);
					$lastAutorefreshMarkup->inline_keyboard[] = BetterLocation::generateRefreshButtons(false);
				}

				if ($collection->isEmpty()) {
					$this->printlog(sprintf('Update %s don\'t have any locations anymore, disabling autorefresh.', $id));
					$msg->text = $messageToRefresh->getLastResponseText() . sprintf('%s Last autorefresh at %s didn\'t detect any locations. Autorefreshing was disabled but you can try to enable it again.', Icons::REFRESH, (new \DateTimeImmutable())->format(Config::DATETIME_FORMAT_ZONE));
					$msg->reply_markup = $lastAutorefreshMarkup;
					$updatedAgo = $now->getTimestamp() - $messageToRefresh->getLastUpdate()->getTimestamp();
					if ($updatedAgo > Config::REFRESH_NO_LOCATION_DISABLE) {
						$messageToRefresh->autorefreshDisable();
						$this->telegramCustomWrapper->run($msg);
					}
				} else {
					$replyMarkup = $processedCollection->getMarkup(1);
					$text = $processedCollection->getText();
					$msg->text = $text . sprintf('%s Autorefreshed: %s', Icons::REFRESH, (new \DateTimeImmutable())->format(Config::DATETIME_FORMAT_ZONE));
					$msg->reply_markup = $replyMarkup;
					$this->telegramCustomWrapper->run($msg);
					$messageToRefresh->setLastSendData($text, $replyMarkup, true);
					$this->printlog(sprintf('Update %s was processed.', $id));
					$messageToRefresh->touchLastUpdate();
				}
			} catch (\Throwable $exception) {
				if ($exception instanceof \unreal4u\TelegramAPI\Exceptions\ClientException && $exception->getMessage() === TelegramHelper::MESSAGE_TO_EDIT_DELETED) {
					$messageToRefresh->autorefreshDisable();
					$this->printlog(sprintf('Message %s, which should be edited was deleted, disabling autorefresh.', $id));
				} else {
					$this->printlog(sprintf('Exception occured while processing %s: %s', $id, $exception->getMessage()));
					\Tracy\Debugger::log($exception, \Tracy\ILogger::EXCEPTION);
				}
			}
		}
		$this->printlog('All updates were processed');
	}

	private function printlog(string $text): void
	{
		$this->log[] = sprintf('%s: %s', (new \DateTime())->format(DATE_W3C), $text);
		\App\Utils\SimpleLogger::log(\App\Utils\SimpleLogger::NAME_CRON_AUTOREFRESH, $text);
	}
}

