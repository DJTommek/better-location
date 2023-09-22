<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\Repository\ChatEntity;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
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

		match ($this->chat->settingsOutputType()) {
			ChatEntity::OUTPUT_TYPE_LOCATION => $this->outputNativeLocation($processedCollection),
			ChatEntity::OUTPUT_TYPE_SNEAK_BUTTONS => $this->outputSneakyButtons($processedCollection),
			default => $this->outputMessage($processedCollection),
		};
	}

	private function outputMessage(ProcessedMessageResult $processedCollection): void
	{
		$text = $processedCollection->getText();
		$markup = $processedCollection->getMarkup(1);
		$response = $this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);

		if ($response && $processedCollection->getCollection()->hasRefreshableLocation()) {
			$cron = new TelegramUpdateDb($this->update, $response->message_id, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
			$cron->insert();
			$cron->setLastSendData($text, $markup, true);
		}
	}

	private function outputNativeLocation(ProcessedMessageResult $processedCollection): void
	{
		$this->replyLocation(
			$processedCollection->getCollection()->getFirst(),
			$processedCollection->getMarkup(1, false),
		);
	}

	private function outputSneakyButtons(ProcessedMessageResult $processedCollection): void
	{
		$edit = new Telegram\Methods\EditMessageReplyMarkup();
		$edit->chat_id = $this->getTgChatId();
		$edit->message_id = $this->getTgMessageId();
		$edit->reply_markup = $processedCollection->getMarkup(1, false);
		try {
			$this->run($edit);
		} catch (ClientException $exception) {
			$error = $exception->getMessage();
			if ($error === TelegramHelper::MESSAGE_CANNOT_BE_EDITED) {
				// Message was sent by some other bot, that already contains some buttons
				// Bot does not have permissions to edit messages of other users
				Debugger::log(sprintf(
					'Unable to append Sneaky Buttons into channel post. Chat ID: "%s", message ID: "%s", error: "%s"',
					$this->getTgChatId(), $this->getTgMessageId(), $error,
				), Debugger::WARNING);
			} else {
				throw $exception;
			}
		}
	}
}
