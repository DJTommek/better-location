<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\GooglePlaceApi;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use unreal4u\TelegramAPI\Telegram;

class ContactEvent extends Special
{
	use UniversalHandleLocationTrait;

	private ?BetterLocationCollection $collection = null;

	public function __construct(
		private readonly ?GooglePlaceApi $googlePlaceApi = null,
	) {
	}

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
		if ($vcard === null || $this->googlePlaceApi === null) {
			return new BetterLocationCollection();
		}

		$parser = new \App\BetterLocation\VcardLocationParser($vcard, $this->googlePlaceApi);
		$parser->process();
		return $parser->getCollection();
	}

	public function handleWebhookUpdate(): void
	{
		$contact = $this->getTgMessage()->contact;
		assert($contact !== null);

		if ($this->getCollection()->isEmpty()) {
			$this->replyEmpty($contact);
			return;
		}

		$this->universalHandle();
	}

	private function replyEmpty(Telegram\Types\Contact $contact): void
	{
		if ($this->isTgPm() !== true) {
			return;
		}

		$message = 'Hi there!' . PHP_EOL;
		$contactDisplayname = $this->contactToName($contact) ?? 'contact';
		$message .= sprintf('Thanks for the %s contact, but I was unable to get location from it.', htmlspecialchars($contactDisplayname));
		$this->reply($message);
	}
}


