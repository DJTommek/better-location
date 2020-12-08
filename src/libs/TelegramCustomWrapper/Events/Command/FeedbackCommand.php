<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;

class FeedbackCommand extends Command
{
	const CMD = '/feedback';

	public function handleWebhookUpdate()
	{
		$messagePrefix = sprintf('%s <b>Feedback</b> for @%s.', Icons::FEEDBACK, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$params = TelegramHelper::getParams($this->update);

		// Using reply
		if ($this->update->message->reply_to_message) {
			if ($this->update->message->reply_to_message->from->username === Config::TELEGRAM_BOT_NAME) {
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
			$text = $messagePrefix;
			$text .= sprintf('Literally <b>Any</b> feedback will be appreciated, especially bad ones!') . PHP_EOL;
			$text .= sprintf('- "<code>%s Thanks for the bot!</code>" to increase morale of authors.', FeedbackCommand::getCmd(!$this->isPm())) . PHP_EOL;
			$text .= sprintf('- "<code>%s I hate this bot, it can\'t do the dishes!</code>" to request more features.', FeedbackCommand::getCmd(!$this->isPm())) . PHP_EOL;
			$text .= PHP_EOL;
			$text .= sprintf('%s Tip: Use reply to any message if you want to authors that specific message why it should (not) be location.', Icons::INFO);
			$this->reply($text);
		}
	}

	private function logFeedback()
	{
		SimpleLogger::log(SimpleLogger::NAME_FEEDBACK, $this->update);
	}
}
