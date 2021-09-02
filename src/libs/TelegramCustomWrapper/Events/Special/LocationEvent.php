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
	/** @var bool is sended location live location */
	private $live;

	public function __construct(Update $update)
	{
		parent::__construct($update);
		$this->live = TelegramHelper::isLocation($this->update, true);
	}

	public function getCollection(): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$betterLocation = BetterLocation::fromLatLon($this->getMessage()->location->latitude, $this->getMessage()->location->longitude);
		if ($this->live) {
			$this->user->setLastKnownLocation($this->getMessage()->location->latitude, $this->getMessage()->location->longitude);
			$betterLocation->setPrefixMessage('Live location');
			$betterLocation->setRefreshable(true);
		} else if (TelegramHelper::isVenue($this->update)) {
			$venue = $this->getMessage()->venue;
			$title = $venue->foursquare_id ? $this->venueHrefLink($venue) : $venue->title;
			$betterLocation->setPrefixMessage('Venue ' . $title);
			$betterLocation->setDescription($this->getMessage()->venue->address);
		} else {
			$betterLocation->setPrefixMessage('Location');
		}
		$collection->add($betterLocation);
		return $collection;
	}

	public function handleWebhookUpdate()
	{
		if ($this->live) {
			$this->user->setLastKnownLocation($this->getMessage()->location->latitude, $this->getMessage()->location->longitude);
		}

		$collection = $this->getCollection();
		$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings());
		$processedCollection->process();
		if ($collection->count() > 0) {
			$markup = $processedCollection->getMarkup(1, false);
			if ($this->chat->getSendNativeLocation()) {
				$this->replyLocation($processedCollection->getCollection()->getFirst(), $markup);
			} else {
				$text = $processedCollection->getText();
				$response = $this->reply($text, $markup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
				if ($response && $collection->hasRefreshableLocation()) {
					$cron = new TelegramUpdateDb($this->update, $response->message_id, TelegramUpdateDb::STATUS_DISABLED, new \DateTimeImmutable());
					$cron->insert();
					$cron->setLastSendData($text, $markup, true);
				}
			}
		} else { // No detected locations or occured errors
			if ($this->isPm() === true) {
				$this->reply(sprintf('%s Unexpected error occured while processing location. Contact Admin for more info.', Icons::ERROR));
			} else {
				// do not send anything to group chat
			}
		}
	}

	private function venueHrefLink(Telegram\Types\Venue $venue)
	{
		return sprintf('<a href="https://foursquare.com/v/%s">%s</a>', $venue->foursquare_id, $venue->title);
	}
}


