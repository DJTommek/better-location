<?php declare(strict_types=1);

namespace TelegramCustomWrapper\Events\Button;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidApiKeyException;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class CronButton extends Button
{
	const CMD = '/cron';

	const ACTION_START = 'start';
	const ACTION_STOP = 'stop';
	const ACTION_REFRESH = 'refresh';

	public function __construct($update) {
		parent::__construct($update);

		$params = TelegramHelper::getParams($update);
		$action = array_shift($params);

		switch ($action) {
			case self::ACTION_START:
				$this->flash(sprintf('%s Enabling automatic refresh is still in progress.', \Icons::ERROR), true);
				break;
			case self::ACTION_STOP:
				$this->flash(sprintf('%s Disabling automatic refresh is still in progress.', \Icons::ERROR), true);
				break;
			case self::ACTION_REFRESH:
				try {
					$this->processRefresh();
				} catch (\Throwable $exception) {
					Debugger::log($exception, ILogger::EXCEPTION);
					$this->flash('%s Unexpected error refreshing location. Try again later or contact Admin for more info.');
				}
				break;
			default:
				$this->flash(sprintf('%s This button (cron) is invalid.%sIf you believe that this is error, please contact admin', \Icons::ERROR, PHP_EOL), true);
				break;
		}
	}

	/**
	 * @throws \Exception
	 */
	private function processRefresh() {
		$collection = BetterLocation::generateFromTelegramMessage(
			$this->update->callback_query->message->reply_to_message->text,
			$this->update->callback_query->message->reply_to_message->entities,
		);
		$result = '';
		$buttonLimit = 1; // @TODO move to config (chat settings)
		$buttons = [];
		foreach ($collection->getAll() as $betterLocation) {
			if ($betterLocation instanceof BetterLocation) {
				$result .= $betterLocation->generateBetterLocation();
				if (count($buttons) < $buttonLimit) {
					$driveButtons = $betterLocation->generateDriveButtons();
					$driveButtons[] = $betterLocation->generateAddToFavouriteButtton();
					$buttons[] = $driveButtons;
				}
			} else if (
				$betterLocation instanceof InvalidLocationException ||
				$betterLocation instanceof InvalidApiKeyException
			) {
				$result .= \Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
			} else {
				$result .= \Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
				Debugger::log($betterLocation, Debugger::EXCEPTION);
			}
		}
		$buttons[] = BetterLocation::generateRefreshButtons(false);
		$now = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone('UTC'));
		$result .= sprintf('%s Last refresh: %s', \Icons::REFRESH, $now->format(\Config::DATETIME_FORMAT_ZONE));
		$markup = (new Markup());
		$markup->inline_keyboard = $buttons;
		$this->replyButton(TelegramHelper::MESSAGE_PREFIX . $result,
			[
				'disable_web_page_preview' => true,
				'reply_markup' => $markup,
			],
		);
		$this->flash(sprintf('%s All locations were refreshed.', \Icons::SUCCESS));
	}
}
