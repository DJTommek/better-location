<?php

namespace TelegramCustomWrapper\Events\Command;

use \BetterLocation\BetterLocation;
use BetterLocation\Service\GoogleMapsService;
use BetterLocation\Service\WazeService;
use \Icons;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class MessageCommand extends Command
{
	/**
	 * MessageCommand constructor.
	 *
	 * @param $update
	 * @param $tgLog
	 * @param $loop
	 * @throws \Exception
	 */
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		// PM or whitelisted group
		$result = null;
		try {
			$betterLocations = BetterLocation::generateFromTelegramMessage($this->getText(), $this->update->message->entities);
			$result = '';
			$firstValidLocation = null;
			foreach ($betterLocations as $betterLocation) {
				if ($betterLocation instanceof BetterLocation) {
					$result .= $betterLocation->generateBetterLocation();
					if (is_null($firstValidLocation)) {
						$firstValidLocation = $betterLocation;
					}
				} else if ($betterLocation instanceof \BetterLocation\Service\Exceptions\InvalidLocationException) {
					$result .= $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
				} else {
					Debugger::log($betterLocation, Debugger::EXCEPTION);
				}
			}
		} catch (\Exception $exception) {
			$this->reply(sprintf('%s Unexpected error occured while processing message for Better location. Contact Admin for more info.\n%s', Icons::ERROR, $exception->getMessage()));
			Debugger::log($exception, ILogger::EXCEPTION);
			return;
		}
		if ($result) {
			$this->reply(
				sprintf('%s <b>Better location</b>', Icons::LOCATION) . PHP_EOL . $result,
				['disable_web_page_preview' => true],
			);
			return;
		} else if ($this->isPm()) {
			$this->reply('Hi there in PM!');
		}
	}
}


