<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Edit;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Factory;
use App\Icons;
use App\TelegramCustomWrapper\ProcessedMessageResult;
use App\TelegramCustomWrapper\TelegramHelper;
use App\TelegramUpdateDb;
use App\Utils\DateImmutableUtils;
use unreal4u\TelegramAPI\Telegram;

class LocationEdit extends Edit
{
	/** @var bool is sended location live location */
	private $live;

	public function __construct(Telegram\Types\Update $update)
	{
		parent::__construct($update);
		$this->live = TelegramHelper::isLocation($update, true);
	}

	public function getCollection(): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		$betterLocation = BetterLocation::fromLatLon($this->getMessage()->location->latitude, $this->getMessage()->location->longitude);
		if ($this->live) {
			$betterLocation->setPrefixMessage('Live location');
			$betterLocation->setRefreshable(true);
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
		if ($messageToRefresh = TelegramUpdateDb::loadByOriginalMessageId($this->getChatId(), $this->getMessageId())) {
			$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings());
			$processedCollection->process();
			$text = $processedCollection->getText();

			// Show datetime of last location update in local timezone based on timezone on that location itself
			$geonames = Factory::Geonames()->timezone($collection->getFirst()->getLat(), $collection->getFirst()->getLon());
			$lastUpdate = DateImmutableUtils::fromTimestamp($this->getMessage()->edit_date, $geonames->timezone);
			$text .= sprintf('%s Last live location from %s', Icons::REFRESH, $lastUpdate->format(Config::DATETIME_FORMAT));

			if ($this->live === false) {
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


