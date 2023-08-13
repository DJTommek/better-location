<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Telegram\LocationService;
use App\Config;
use App\Factory;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;
use unreal4u\TelegramAPI\Telegram;

class LocationEdit extends Edit
{
	private bool $isLive;
	private ?BetterLocationCollection $collection = null;

	public function __construct(Telegram\Types\Update $update)
	{
		parent::__construct($update);
		$this->isLive = TelegramHelper::isLocation($update, true);
	}

	public function getCollection(): BetterLocationCollection
	{
		if ($this->collection === null) {
			$this->collection = new BetterLocationCollection();

			$location = $this->getTgMessage()->location;

			$type = $this->isLive ? LocationService::TYPE_LIVE : LocationService::TYPE_CLASSIC;
			$betterLocation = BetterLocation::fromLatLon($location->latitude, $location->longitude, LocationService::class, $type);
			if ($this->isLive) {
				$betterLocation->setPrefixMessage('Live location');
				$betterLocation->setRefreshable(true);
			} else {
				$betterLocation->setPrefixMessage('Location');
			}
			$this->collection->add($betterLocation);
		}
		return $this->collection;
	}

	public function handleWebhookUpdate(): void
	{
		$tgMessage = $this->getTgMessage();
		$messageEditDate = $this->getTgMessageEditDate();

		if ($this->isLive) {
			$this->user->setLastKnownLocation(
				$tgMessage->location->latitude,
				$tgMessage->location->longitude,
				$messageEditDate,
			);
		}

		$collection = $this->getCollection();
		if ($messageToRefresh = TelegramUpdateDb::loadByOriginalMessageId($this->getTgChatId(), $this->getTgMessageId())) {
			$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
			$processedCollection->process();
			$text = $processedCollection->getText();

			// Show datetime of last location update in local timezone based on timezone on that location itself
			$geonames = Factory::geonames()->timezone($collection->getFirst()->getLat(), $collection->getFirst()->getLon());
			$text .= sprintf(
				'%s Last update %s',
				Icons::REFRESH,
				$messageEditDate->setTimezone($geonames->timezone)->format(Config::DATETIME_FORMAT),
			);

			if ($this->isLive === false) {
				// If user cancel sharing, edit event is fired but it's not live location anymore.
				// But if sharing is expired (automatically), TG server is not sending any edit event.
				$text .= ' (sharing has stopped)';
			}

			$replyMarkup = $processedCollection->getMarkup(1, false);

			$editMessage = new \unreal4u\TelegramAPI\Telegram\Methods\EditMessageText();
			$editMessage->chat_id = $messageToRefresh->getChatId();
			$editMessage->message_id = $messageToRefresh->getBotReplyMessageId();
			$editMessage->parse_mode = 'HTML';
			$editMessage->disable_web_page_preview = !$this->chat->settingsPreview();
			$editMessage->text = $text;
			$editMessage->reply_markup = $replyMarkup;
			$this->run($editMessage);

			$messageToRefresh->setLastSendData($text, $replyMarkup, true);
			$messageToRefresh->touchLastUpdate();
		}
	}
}


