<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\TelegramUpdateDb;
use App\Icons;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;

class MessageEvent extends Special
{
	public function __construct($update)
	{
		parent::__construct($update);

		$collection = BetterLocationCollection::fromTelegramMessage($this->getText(), $this->update->message->entities);
		$processedCollection = new ProcessedMessageResult($collection);
		$processedCollection->process();
		if ($collection->count() > 0) {
			$this->reply(
				TelegramHelper::MESSAGE_PREFIX . $processedCollection->getText(),
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $processedCollection->getMarkup(1),
				],
			);
			if ($collection->hasRefreshableLocation()) {
				$cron = new TelegramUpdateDb($update, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
				$cron->insert();
			}
		} else { // No detected locations or occured errors
			if ($this->isPm() === true) {
				$message = 'Hi there in PM!' . PHP_EOL;
				$message .= 'Thanks for the ';
				if ($this->isForward()) {
					$message .= 'forwarded ';
				}
				$message .= sprintf('message, but I didn\'t detected any location in that message. Use %s command to get info how to use me.', HelpCommand::getCmd(!$this->isPm())) . PHP_EOL;
				$message .= sprintf('%s Most used tips: ', Icons::INFO) . PHP_EOL;
				$message .= '- send me any message with location data (coords, links, Telegram location...)' . PHP_EOL;
				$message .= '- send me Telegram location' . PHP_EOL;
				$message .= '- send me <b>uncompressed</b> photos (as file) to process location from EXIF' . PHP_EOL;
				$message .= '- forward me any of above' . PHP_EOL;
				$this->reply($message);
			} else {
				// do not send anything to group chat
			}
		}
	}
}


