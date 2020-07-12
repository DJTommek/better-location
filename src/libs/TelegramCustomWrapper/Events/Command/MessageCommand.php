<?php

namespace TelegramCustomWrapper\Events\Command;

use \BetterLocation\BetterLocation;
use \Icons;
use Tracy\Debugger;
use Tracy\ILogger;

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
		if ($this->isPm() || in_array($this->getChatId(), BetterLocation::TELEGRAM_GROUP_WHITELIST)) {
			$result = null;
			try {
				$betterLocation = new BetterLocation($this->getText(), $this->update->message->entities);
				$result = $betterLocation->processMessage();
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
			}
		}

		if ($this->isPm()) {
			$this->reply('Hi there in PM!');
		}
	}
}


