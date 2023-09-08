<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\SimpleLogger;

class FeedbackCommand extends Command
{
	const CMD = '/feedback';
	const ICON = Icons::FEEDBACK;
	const DESCRIPTION = 'Report invalid location or just contact the author @DJTommek';

	public function handleWebhookUpdate(): void
	{
		$messagePrefix = sprintf('%s <b>Feedback</b> for @%s.', Icons::FEEDBACK, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$hasParams = $this->params !== [];

		// Using reply
		if ($this->isTgMessageReply()) {
			$replySenderTgId = $this->getTgMessage()->reply_to_message->from->username;
			if ($replySenderTgId === Config::TELEGRAM_BOT_NAME) {
				$this->reply($messagePrefix . 'Thanks for reporting, my BetterLocation message will be reviewed.');
			} else if ($hasParams) {
				$this->reply($messagePrefix . 'Thanks for reporting, message marked in reply will be reviewed.');
			} else {
				$this->reply($messagePrefix . 'Message marked in reply will be reviewed but please add some description to it, for example if and why it should (not) be valid location.');
			}
			$this->logFeedback();
			return;
		}

		if ($hasParams) {
			$this->reply($messagePrefix . 'Thanks for your feedback! You will be contacted in case it is necessary.');
			$this->logFeedback();
			return;
		}

		$text = $messagePrefix;
		$text .= sprintf('Literally <b>Any</b> feedback will be appreciated, especially bad ones!') . PHP_EOL;
		$text .= sprintf('- "<code>%s Thanks for the bot!</code>" to increase morale of authors.', FeedbackCommand::getTgCmd(!$this->isTgPm())) . PHP_EOL;
		$text .= sprintf('- "<code>%s I hate this bot, it can\'t do the dishes!</code>" to request more features.', FeedbackCommand::getTgCmd(!$this->isTgPm())) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s Tip: Use reply to any message if you want to authors that specific message why it should (not) be location.', Icons::INFO);
		$this->reply($text);
	}

	private function logFeedback(): void
	{
		SimpleLogger::log(SimpleLogger::NAME_FEEDBACK, $this->update);
	}
}
