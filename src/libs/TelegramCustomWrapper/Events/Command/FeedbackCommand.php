<?php

namespace TelegramCustomWrapper\Events\Command;

use \Icons;
use TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use Utils\DummyLogger;

class FeedbackCommand extends Command
{
	/**
	 * FeedbackCommand constructor.
	 *
	 * @param Update $update
	 * @throws \Exception
	 */
	public function __construct(Update $update) {
		parent::__construct($update);

		$messagePrefix = sprintf('%s <b>Feedback</b> for @%s.', Icons::FEEDBACK, \Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$params = TelegramHelper::getParams($update);

		// Using reply
		if ($update->message->reply_to_message) {
			if ($update->message->reply_to_message->from->username === \Config::TELEGRAM_BOT_NAME) {
				$this->logFeedback();
				// @TODO adjust condition to match only real BetterLocation message, not just any message from bot
				$this->reply($messagePrefix . 'Thanks for reporting, my BetterLocation message will be reviewed.');
			} else if (count($params)) {
				$this->logFeedback();
				$this->reply($messagePrefix . 'Thanks for reporting, message marked in reply will be reviewed.');
			} else {
				$this->logFeedback();
				$this->reply($messagePrefix . 'Message marked in reply will be reviewed but please add some description to it, for example if and why it should (not) be valid location.');
			}
		} else if (count($params)) {
			$this->logFeedback();
			$this->reply($messagePrefix . 'Thanks for your feedback! You will be contacted in case it is necessary.');
		} else {
			$this->reply(
				$messagePrefix .
				'Literally <b>Any</b> feedback will be appreciated, especially bad ones!' . PHP_EOL .
 				'- "<code>/feedback Thanks for the bot!</code>" to increase morale of authors.' . PHP_EOL .
 				'- "<code>/feedback I hate this bot, it can\'t do the dishes!</code>" to request more features.' . PHP_EOL .
				''. PHP_EOL .
				sprintf('%s Tip: Use reply to any message if you want to authors that specific message why it should (not) be location.', Icons::INFO)
			);
		}
	}

	private function logFeedback() {
		\Utils\DummyLogger::log(DummyLogger::NAME_FEEDBACK, $this->update);
	}
}
