<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;

class PhotoEvent extends Special
{
	public function getCollection(): BetterLocationCollection {
		return BetterLocationCollection::fromTelegramMessage(
			$this->update->message->caption,
			$this->update->message->caption_entities,
		);
	}

	public function handleWebhookUpdate()
	{
		$collection = $this->getCollection();
		$processedCollection = new ProcessedMessageResult($collection);
		$processedCollection->process();
		if ($collection->count() > 0) {
			$response = $this->reply(
				TelegramHelper::MESSAGE_PREFIX . $processedCollection->getText(),
				[
					'disable_web_page_preview' => true,
					'reply_markup' => $processedCollection->getMarkup(1),
				],
			);
			if ($collection->hasRefreshableLocation()) {
				$cron = new TelegramUpdateDb($this->update, $response->message_id, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
				$cron->insert();
			}
		} else { // No detected locations or occured errors
			if ($this->isPm() === true) {
				$message = 'Hi there in PM!' . PHP_EOL;
				$message .= 'Thanks for the ';
				if ($this->isForward()) {
					$message .= 'forwarded ';
				}
				$message .= 'photo but I\'m not sure, what to do... If you want to process location from EXIF, you have to send <b>uncompressed</b> photo (send as file).';
				$this->reply($message);
			} else {
				// do not send anything to group chat
			}
		}
	}
}


