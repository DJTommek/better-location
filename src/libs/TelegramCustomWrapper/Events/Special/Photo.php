<?php

namespace TelegramCustomWrapper\Events\Special;

use \Icons;
use BetterLocation\BetterLocation;

class Photo extends \TelegramCustomWrapper\Events\Special\Special
{
	/**
	 * PhotoCommand constructor.
	 *
	 * @param $update
	 * @param $tgLog
	 * @param $loop
	 * @throws \Exception
	 */
	public function __construct($update, $tgLog, $loop) {
		parent::__construct($update, $tgLog, $loop);

		// PM or whitelisted group
		if ($this->isPm() || in_array($this->getChatId(), BetterLocation::TELEGRAM_GROUP_WHITELIST)) {
			$result = null;
			try {
				$betterLocation = new BetterLocation($this->update->message->caption, $this->update->message->caption_entities);
				$result = $betterLocation->processMessage();
			} catch (\Exception $exception) {
				$this->reply(sprintf('%s Unexpected error occured while processing photo caption for Better location. Contact Admin for more info.', Icons::ERROR));
				throw $exception;
			}
			if ($result) {
				$this->reply(
					sprintf('%s <b>Better location</b>', Icons::LOCATION) . PHP_EOL . $result,
					['disable_web_page_preview' => true],
				);
				return;
			}
		}

		if ($this->isPm()) {
			$this->reply('Thanks for the photo in PM! But I\'m not sure, what to do...');
		}
	}
}


