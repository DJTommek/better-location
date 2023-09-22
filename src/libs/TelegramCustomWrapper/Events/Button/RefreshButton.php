<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Icons;
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

	/** @var TelegramUpdateDb */
	private $telegramUpdateDb;

	public function handleWebhookUpdate(): void
	{
		try {
			$params = TelegramHelper::getParams($this->update);
			$action = array_shift($params);
			$this->telegramUpdateDb = TelegramUpdateDb::fromDb(
				$this->getTgChatId(),
				$this->getTgMessageId(),
			);

			switch ($action) {
				case self::ACTION_START:
					if ($this->telegramUpdateDb->isAutorefreshEnabled()) {
						$this->processRefresh(true, true);
						$this->flash(sprintf('%s Autorefresh was already enabled.', Icons::SUCCESS), true);
					} else {
						$autorefreshList = TelegramUpdateDb::loadAll(TelegramUpdateDb::STATUS_ENABLED, $this->getTgChatId());
						if (count($autorefreshList) >= Config::REFRESH_AUTO_MAX_PER_CHAT) {
							$this->flash(sprintf('%s You already have %d autorefresh enabled, which is maximum per one chat.', Icons::ERROR, count($autorefreshList)), true);
						} else {
							$this->telegramUpdateDb->autorefreshEnable();
							$this->processRefresh(true, true);
							$this->flash(sprintf('%s Autorefresh is now enabled.', Icons::SUCCESS), true);
						}
					}
					break;
				case self::ACTION_STOP:
					if ($this->telegramUpdateDb->isAutorefreshEnabled() === false) {
						$this->processRefresh(false, true);
						$this->flash(sprintf('%s Autorefresh was already disabled.', Icons::SUCCESS), true);
					} else {
						$this->telegramUpdateDb->autorefreshDisable();
						$this->processRefresh(false, true);
						$this->flash(sprintf('%s Autorefresh is now disabled.', Icons::SUCCESS), true);
					}
					break;
				case self::ACTION_REFRESH:
					$diff = (new \DateTimeImmutable())->getTimestamp() - $this->telegramUpdateDb->getLastUpdate()->getTimestamp();
					if ($diff < Config::REFRESH_COOLDOWN) {
						$this->flash(sprintf('%s You need to wait %d more seconds before another refresh.', Icons::ERROR, Config::REFRESH_COOLDOWN - $diff), true);
					} else {
						$this->processRefresh($this->telegramUpdateDb->isAutorefreshEnabled(), false);
						$this->flash(sprintf('%s All locations were refreshed.', Icons::SUCCESS));
					}
					break;
				default:
					$this->flash(sprintf('%s This button (cron) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, PHP_EOL), true);
					break;
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			$this->flash(sprintf('%s Unexpected error while processing autorefresh, please contact admin for more info.', Icons::ERROR), true);
		}
	}

	/**
	 * @throws \Exception
	 */
	private function processRefresh(bool $autorefreshEnabled, bool $fromCache)
	{
		if ($fromCache) {
			$text = $this->telegramUpdateDb->getLastResponseText();
			$text .= sprintf('%s Last refresh: %s', Icons::REFRESH, $this->telegramUpdateDb->getLastUpdate()->format(Config::DATETIME_FORMAT_ZONE));

			$markup = $this->telegramUpdateDb->getLastResponseReplyMarkup(true);
			unset($markup->inline_keyboard[count($markup->inline_keyboard)-1]); // refresh buttons are always last row
			$markup->inline_keyboard[] = BetterLocation::generateRefreshButtons($autorefreshEnabled);

			$this->replyButton($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		} else {
			$collection = BetterLocationCollection::fromTelegramMessage(
				$this->telegramUpdateDb->originalUpdateObject->message->text,
				$this->telegramUpdateDb->originalUpdateObject->message->entities,
			);
			$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
			$processedCollection->setAutorefresh($autorefreshEnabled);
			$processedCollection->process();
			$text = $processedCollection->getText();
			$text .= sprintf('%s Last refresh: %s', Icons::REFRESH, (new \DateTimeImmutable())->format(Config::DATETIME_FORMAT_ZONE));
			if (count($collection->getLocations()) > 0) {
				$this->replyButton($text, $processedCollection->getMarkup(1), ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
			} else {
				// @TODO if returned location would remove refresh buttons (no refreshable location) or returned error, do not update
				// original message, just send warning that update can't be completed
				// or at least keep original update and add warning below that message
			}
			$this->telegramUpdateDb->touchLastUpdate();
		}
	}
}
