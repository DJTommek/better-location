<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramCustomWrapper\UniversalHandleLocationTrait;
use unreal4u\TelegramAPI\Telegram;

class LocationEvent extends Special
{
	use UniversalHandleLocationTrait;

	private bool $isLive;
	private ?BetterLocationCollection $collection = null;

	protected function afterInit(): void
	{
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

	private function includeRefreshRow(): bool
	{
		return false;
	}

	private function allowSneakyButtons(): bool
	{
		if ($this->isLive) {
			return false;
		}
		return true;
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

		assert($this->getCollection()->isEmpty() === false);

		$this->universalHandle();
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


