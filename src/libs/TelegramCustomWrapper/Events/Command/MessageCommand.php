<?php

namespace TelegramCustomWrapper\Events\Command;

use \BetterLocation\BetterLocation;
use BetterLocation\Service\GoogleMapsService;
use BetterLocation\Service\WazeService;
use \Icons;
use TelegramCustomWrapper\TelegramHelper;
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
	 * @throws \Exception
	 */
	public function __construct($update) {
		parent::__construct($update);

		// PM or whitelisted group
		$result = null;
		try {
			$betterLocations = BetterLocation::generateFromTelegramMessage($this->getText(), $this->update->message->entities);
			$result = '';
			$buttonLimit = 1; // @TODO move to config (chat settings)
			$buttons = [];
			foreach ($betterLocations as $betterLocation) {
				if ($betterLocation instanceof BetterLocation) {
					$result .= $betterLocation->generateBetterLocation();
					if (count($buttons) < $buttonLimit) {
						$buttons[] = $this->getDriveButtons($betterLocation);
					}
				} else if (
					$betterLocation instanceof \BetterLocation\Service\Exceptions\InvalidLocationException ||
					$betterLocation instanceof \BetterLocation\Service\Exceptions\InvalidApiKeyException
				) {
					$result .= Icons::ERROR . $betterLocation->getMessage() . PHP_EOL . PHP_EOL;
				} else {
					$result .= Icons::ERROR . 'Unexpected error occured while proceessing message for locations.' . PHP_EOL . PHP_EOL;
					Debugger::log($betterLocation, Debugger::EXCEPTION);
				}
			}
		} catch (\Exception $exception) {
			$this->reply(sprintf('%s Unexpected error occured while processing message for Better location. Contact Admin for more info.\n%s', Icons::ERROR, $exception->getMessage()));
			Debugger::log($exception, ILogger::EXCEPTION);
			return;
		}
		if ($result) {
			$markup = (new Markup());
			$markup->inline_keyboard = $buttons;
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $result,
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $markup,
				],
			);
		} else if ($this->isPm()) {
			$this->reply('Hi there in PM!');
		}
	}

	private function getDriveButtons(BetterLocation $betterLocation) {
		$googleButton = new Button();
		$googleButton->text = 'Google ' . Icons::CAR;
		$googleButton->url = $betterLocation->getLink(new GoogleMapsService, true);

		$wazeButton = new Button();
		$wazeButton->text = 'Waze ' . Icons::CAR;
		$wazeButton->url = $betterLocation->getLink(new WazeService(), true);

		return [$googleButton, $wazeButton];
	}
}


