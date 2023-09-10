<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramUpdateDb;
use unreal4u\TelegramAPI\Telegram;

class ChannelPostEvent extends Special
{
	private ?BetterLocationCollection $collection = null;

	public function getTgMessage(): Telegram\Types\Message
	{
		return $this->update->channel_post;
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = BetterLocationCollection::fromTelegramMessage(
				$this->getTgText(),
				$this->getTgMessage()->entities,
			);
		}
		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		$collection = $this->getCollection();

		$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
		$processedCollection->process();

		if ($collection->isEmpty()) {
			return;
		}

		if ($this->chat->getSendNativeLocation()) {
			$this->replyLocation(
				$processedCollection->getCollection()->getFirst(),
				$processedCollection->getMarkup(1, false),
			);
			return;
		}

		$text = $processedCollection->getText();
		$markup = $processedCollection->getMarkup(1);
		$response = $this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		if ($response && $collection->hasRefreshableLocation()) {
			$cron = new TelegramUpdateDb($this->update, $response->message_id, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
			$cron->insert();
			$cron->setLastSendData($text, $markup, true);
		}
	}
}
