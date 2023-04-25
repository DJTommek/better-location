<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\Factory;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use unreal4u\TelegramAPI\Telegram;

class ContactEvent extends Special
{
	private ?BetterLocationCollection $collection = null;

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = $this->getCollectionFromContact();
		}

		return $this->collection;
	}

	private function contactToName(Telegram\Types\Contact $contact): ?string
	{
		$result = trim($contact->first_name . ' ' . $contact->last_name);
		return $result === '' ? null : $result;
	}

	private function getCollectionFromContact(): BetterLocationCollection
	{
		$vcard = $this?->update?->message?->contact?->vcard ?? null;
		if ($vcard === null) {
			return new BetterLocationCollection();
		}

		$googleSearching = Factory::googlePlaceApi();

		$parser = new \App\BetterLocation\VcardLocationParser($vcard, $googleSearching);
		$parser->process();
		return $parser->getCollection();
	}

	public function handleWebhookUpdate(): void
	{
		$contact = $this->update->message->contact;
		assert($contact !== null);

		if ($this->getCollection()->isEmpty()) {
			$this->replyEmpty($contact);
			return;
		}

		$processedCollection = new ProcessedMessageResult($this->getCollection(), $this->getMessageSettings(), $this->getPluginer());
		$processedCollection->process();
		$markup = $processedCollection->getMarkup(1, false);
		if ($this->chat->getSendNativeLocation()) {
			$firstLocation = $processedCollection->getCollection()->getFirst();
			$this->replyLocation($firstLocation, $markup);
			return;
		}

		$text = $processedCollection->getText();
		$this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
	}

	private function replyEmpty(Telegram\Types\Contact $contact): void
	{
		if ($this->isTgPm() === true) {
			$message = 'Hi there!' . PHP_EOL;
			$contactDisplayname = $this->contactToName($contact) ?? 'contact';
			$message .= sprintf('Thanks for the %s contact, but I was unable to get location from it.', htmlspecialchars($contactDisplayname));
			$this->reply($message);
		}
	}
}


