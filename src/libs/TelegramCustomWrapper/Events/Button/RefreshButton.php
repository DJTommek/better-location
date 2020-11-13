<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Exceptions\MessageDeletedException;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;
use Tracy\Debugger;
use Tracy\ILogger;

class RefreshButton extends Button
{
	const CMD = '/refresh';

	const ACTION_START = 'start';
	const ACTION_STOP = 'stop';
	const ACTION_REFRESH = 'refresh';

	private $telegramUpdateDb;

	public function __construct($update)
	{
		parent::__construct($update);
		try {
			$params = TelegramHelper::getParams($update);
			$action = array_shift($params);
			$this->telegramUpdateDb = TelegramUpdateDb::fromDb(
				$this->update->callback_query->message->reply_to_message->chat->id,
				$this->update->callback_query->message->reply_to_message->message_id,
			);

			switch ($action) {
				case self::ACTION_START:
					if ($this->telegramUpdateDb->isAutorefreshEnabled()) {
						$this->processRefresh(true);
						$this->flash(sprintf('%s Autorefresh was already enabled.', Icons::SUCCESS), true);
					} else {
						$autorefreshList = TelegramUpdateDb::loadAll(TelegramUpdateDb::STATUS_ENABLED, $this->getChatId());
						if (count($autorefreshList) >= Config::REFRESH_AUTO_MAX_PER_CHAT) {
							$this->flash(sprintf('%s You already have %d autorefresh enabled, which is maximum per one chat.', Icons::ERROR, count($autorefreshList)), true);
						} else {
							$this->telegramUpdateDb->autorefreshEnable();
							$this->processRefresh(true);
							$this->flash(sprintf('%s Autorefresh is now enabled.', Icons::SUCCESS), true);
						}
					}
					break;
				case self::ACTION_STOP:
					if ($this->telegramUpdateDb->isAutorefreshEnabled() === false) {
						$this->processRefresh(false);
						$this->flash(sprintf('%s Autorefresh was already disabled.', Icons::SUCCESS), true);
					} else {
						$this->telegramUpdateDb->autorefreshDisable();
						$this->processRefresh(false);
						$this->flash(sprintf('%s Autorefresh is now disabled.', Icons::SUCCESS), true);
					}
					break;
				case self::ACTION_REFRESH:
					$diff = (new \DateTimeImmutable())->getTimestamp() - $this->telegramUpdateDb->getLastUpdate()->getTimestamp();
					if ($diff < Config::REFRESH_COOLDOWN) {
						$this->flash(sprintf('%s You need to wait %d more seconds before another refresh.', Icons::ERROR, Config::REFRESH_COOLDOWN - $diff), true);
					} else {
						try {
							$this->processRefresh($this->telegramUpdateDb->isAutorefreshEnabled());
							$this->flash(sprintf('%s All locations were refreshed.', Icons::SUCCESS));
						} catch (\Throwable $exception) {
							Debugger::log($exception, ILogger::EXCEPTION);
							$this->flash('%s Unexpected error refreshing location. Try again later or contact Admin for more info.');
						}
					}
					break;
				default:
					$this->flash(sprintf('%s This button (cron) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, PHP_EOL), true);
					break;
			}
		} catch (MessageDeletedException $exception) {
			$this->flash(sprintf('%s Location can\'t be refreshed, original message was deleted.', Icons::ERROR), true);
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Unexpected error while processing autorefresh, please contact admin for more info.', Icons::ERROR), true);
		}
	}

	/**
	 * @throws \Exception
	 */
	private function processRefresh(bool $autorefreshEnabled)
	{
		$collection = BetterLocationCollection::fromTelegramMessage(
			$this->telegramUpdateDb->getUpdate()->message->text,
			$this->telegramUpdateDb->getUpdate()->message->entities,
		);
		$processedCollection = new ProcessedMessageResult($collection);
		$processedCollection->setAutorefresh($autorefreshEnabled);
		$processedCollection->process();
		$text = TelegramHelper::MESSAGE_PREFIX . $processedCollection->getText();
		$text .= sprintf('%s Last refresh: %s', Icons::REFRESH, (new \DateTimeImmutable())->format(Config::DATETIME_FORMAT_ZONE));
		if ($collection->count() > 0) {
			$this->replyButton($text,
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $processedCollection->getMarkup(1),
				],
			);
		}
		$this->telegramUpdateDb->touchLastUpdate();

//		$result = '';
//		$buttonLimit = 1; // @TODO move to config (chat settings)
//		$buttons = [];
//		foreach ($collection->getAll() as $betterLocation) {
//			if ($betterLocation instanceof BetterLocation) {
//				$result .= $betterLocation->generateBetterLocation();
//				if (count($buttons) < $buttonLimit) {
//					$driveButtons = $betterLocation->generateDriveButtons();
//					$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
//					$buttons[] = $driveButtons;
//				}
//			} else if (
//				$betterLocation instanceof InvalidLocationException ||
//				$betterLocation instanceof InvalidApiKeyException
//			) {
//				$result .= Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
//			} else {
//				$result .= Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
//				Debugger::log($betterLocation, Debugger::EXCEPTION);
//			}
//		}
//		$buttons[] = BetterLocation::generateRefreshButtons($isCronEnabled);
//		$now = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
//		$result .= sprintf('%s Last refresh: %s', Icons::REFRESH, $now->format(Config::DATETIME_FORMAT_ZONE));
//		$markup = (new Markup());
//		$markup->inline_keyboard = $buttons;
//		$this->replyButton(TelegramHelper::MESSAGE_PREFIX . $result,
//			[
//				'disable_web_page_preview' => true,
//				'reply_markup' => $markup,
//			],
//		);
	}
}
