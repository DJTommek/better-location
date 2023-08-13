<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LocationEvent extends Special
{
	private bool $isLive;
	private ?BetterLocationCollection $collection = null;

	public function __construct(Update $update)
	{
		parent::__construct($update);
		$this->isLive = TelegramHelper::isLocation($this->update, true);
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = new BetterLocationCollection();

			$lat = $this->getTgMessage()->location->latitude;
			$lon = $this->getTgMessage()->location->longitude;
			$betterLocation = BetterLocation::fromLatLon($lat, $lon);

			if ($this->isLive) {
				$betterLocation->setPrefixMessage('Live location');
				$betterLocation->setRefreshable(true);
			} else if (TelegramHelper::isVenue($this->update)) {
				$venue = $this->getTgMessage()->venue;
				$title = $venue->foursquare_id ? $this->venueHrefLink($venue) : $venue->title;
				$betterLocation->setPrefixMessage('Venue ' . $title);

				// Venue address is mostly very inaccurate, so it cannot be used instead as real address.
				// Examples 'Prague', 'Köln', 'Želetavská', 'Holandská 1052/52', 'Siegburgerstr. 229', ...
				$betterLocation->addDescription($this->getTgMessage()->venue->address);
			} else {
				$betterLocation->setPrefixMessage('Location');
			}

			$this->collection->add($betterLocation);
		}
		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->isLive) {
			$location = $this->getTgMessage()->location;
			$this->user->setLastKnownLocation(
				$location->latitude,
				$location->longitude,
				$this->getTgMessageSentDate(),
			);
		}

		$collection = $this->getCollection();
		$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
		$processedCollection->process();

		if ($collection->isEmpty()) { // No detected locations or occured errors
			if ($this->isTgPm()) {
				$this->reply(sprintf('%s Unexpected error occured while processing location. Contact Admin for more info.', Icons::ERROR));
			} else {
				// do not send anything to group chat
			}
			return;
		}

		$markup = $processedCollection->getMarkup(1, false);
		if ($this->chat->getSendNativeLocation()) {
			$this->replyLocation($processedCollection->getCollection()->getFirst(), $markup);
			return;
		}

		$text = $processedCollection->getText();
		$response = $this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		if ($response && $collection->hasRefreshableLocation()) {
			$cron = new TelegramUpdateDb($this->update, $response->message_id, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
			$cron->insert();
			$cron->setLastSendData($text, $markup, true);
		}
	}

	private function venueHrefLink(Telegram\Types\Venue $venue): string
	{
		return sprintf(
			'<a href="https://foursquare.com/v/%s">%s</a>',
			$venue->foursquare_id,
			htmlspecialchars($venue->title),
		);
	}
}


