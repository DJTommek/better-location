<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\BetterLocation\BetterLocationCollection;
use App\Chat;
use App\Pluginer\Pluginer;
use App\Repository\ChatEntity;
use App\TelegramUpdateDb;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

trait UniversalHandleLocationTrait
{
	abstract function getCollection(): BetterLocationCollection;

	abstract function getMessageSettings(): BetterLocationMessageSettings;

	abstract function getPluginer(): ?Pluginer;

	abstract function replyLocation(CoordinatesInterface $location, ?Markup $markup = null): ?Telegram\Types\Message;

	abstract function reply(string $text, ?Markup $markup = null, array $options = []): ?Telegram\Types\Message;

	abstract function getUpdate(): Telegram\Types\Update;

	abstract function getTgChatId(): int;

	abstract function getTgMessageId(): int;

	abstract function getChat(): ?Chat;

	abstract function isTgForward(): bool;

	abstract function runSmart(TelegramMethods $objectToSend): ?TelegramTypes;

	/**
	 * Can be overriden
	 */
	private function includeRefreshRow(): bool
	{
		return true;
	}

	/**
	 * Can be overriden
	 */
	private function allowSneakyButtons(): bool
	{
		return true;
	}

	public function universalHandle(): void
	{
		$collection = $this->getCollection();

		$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
		$processedCollection->process();

		if ($collection->isEmpty()) {
			return;
		}

		match ($this->getChat()->settingsOutputType()) {
			ChatEntity::OUTPUT_TYPE_LOCATION => $this->outputNativeLocation($processedCollection),
			ChatEntity::OUTPUT_TYPE_SNEAK_BUTTONS => $this->outputSneakyButtons($processedCollection),
			default => $this->outputMessage($processedCollection),
		};
	}

	private function outputMessage(ProcessedMessageResult $processedCollection): void
	{
		$text = $processedCollection->getText();
		$markup = $processedCollection->getMarkup(1, $this->includeRefreshRow());
		$response = $this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);

		$this->addToUpdateDb($processedCollection, $response, $text, $markup);
	}

	private function outputNativeLocation(ProcessedMessageResult $processedCollection): void
	{
		$location = $processedCollection->getCollection()->getFirst();
		$markup = $processedCollection->getMarkup(1, false);
		$response = $this->replyLocation($location, $markup,);
	}

	private function outputSneakyButtons(ProcessedMessageResult $processedCollection): void
	{
		if ($this->allowSneakyButtons() === false) {
			return;
		}

		if ($this->isTgForward()) {
			return;
		}

		$markup = $processedCollection->getMarkup(1, false);
		assert(TelegramHelper::isMarkupEmpty($markup) === false, 'Markup should not be empty, probably check for error in chat settings validation.');

		$edit = new Telegram\Methods\EditMessageReplyMarkup();
		$edit->chat_id = $this->getTgChatId();
		$edit->message_id = $this->getTgMessageId();
		$edit->reply_markup = $markup;
		try {
			$this->runSmart($edit);
		} catch (ClientException $exception) {
			$error = $exception->getMessage();
			if ($error === TelegramHelper::MESSAGE_CANNOT_BE_EDITED) {
				// Message was sent by some other bot, that already contains some buttons
				// Bot does not have permissions to edit messages of other users
				Debugger::log(sprintf(
					'Unable to append Sneaky Buttons into channel post. Chat ID: "%s", message ID: "%s", error: "%s"',
					$this->getTgChatId(),
					$this->getTgMessageId(),
					$error,
				),
					Debugger::WARNING);
			} else {
				throw $exception;
			}
		}

	}

	private function addToUpdateDb(
		ProcessedMessageResult $processedCollection,
		?Telegram\Types\Message $response,
		string $text,
		Markup $markup,
	): void {
		if ($response === null) {
			return;
		}

		if ($processedCollection->getCollection()->hasRefreshableLocation() === false) {
			return;
		}

		try {
			$cron = new TelegramUpdateDb(
				originalUpdateObject: $this->getUpdate(),
				telegramChatId: $this->getTgChatId(),
				inputMessageId: $this->getTgMessageId(),
				messageIdToEdit: $response->message_id,
				status: TelegramUpdateDb::STATUS_DISABLED,
				lastUpdate: new \DateTimeImmutable(),
			);
			$cron->insert();
			$cron->setLastSendData($text, $markup, true);
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}
}
