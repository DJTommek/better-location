<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram\Types\Update;

class LocationEvent extends Special
{
	/** @var bool is sended location live location */
	private $live;

	public function __construct(Update $update)
	{
		parent::__construct($update);
		$this->live = TelegramHelper::isLocation($update, true);

		$collection = new BetterLocationCollection();

		$betterLocation = BetterLocation::fromLatLon($this->update->message->location->latitude, $this->update->message->location->longitude);
		if ($this->live) {
			$this->user->setLastKnownLocation($this->update->message->location->latitude, $this->update->message->location->longitude);
			$betterLocation->setPrefixMessage('Live location');
		} else {
			$betterLocation->setPrefixMessage('Location');
		}
		$collection->add($betterLocation);

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
		} else { // No detected locations or occured errors
			if ($this->isPm() === true) {
				$this->reply(sprintf('%s Unexpected error occured while processing location. Contact Admin for more info.', Icons::ERROR));
			} else {
				// do not send anything to group chat
			}
		}
	}
}


