<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\BetterLocation\BetterLocationCollection;
use App\Icons;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;

class ChannelMessage extends Special
{

	public function getMessage(): Telegram\Types\Message
	{
		return $this->update->channel_post;
	}

	public function getFrom(): Telegram\Types\User
	{
		throw new \Exception(sprintf('Type %s doesn\'t support getFrom().', static::class));
	}

	/**
	 * @return int
	 */
	public function getFromId(): int
	{
		return $this->getMessage()->sender_chat->id;
	}

	public function getFromDisplayname(): string
	{
		return $this->getMessage()->sender_chat->title;
	}

	public function getCollection(): BetterLocationCollection
	{
		return BetterLocationCollection::fromTelegramMessage($this->getText(), $this->getMessage()->entities);
	}

	public function handleWebhookUpdate()
	{
		// @TODO editing message will remove all original formatting.
		// editMessageText->entities should be used (docs: https://core.telegram.org/bots/api#editmessagetext)

		$editMessage = new Telegram\Methods\EditMessageText();
		$editMessage->chat_id = $this->getChatId();
		$editMessage->message_id = $this->getMessageId();
		$editMessage->parse_mode = 'HTML';

		if ($location = $this->getCollection()->getFirst()) {

			// @TODO load settings for channel instead of default
			$chatMessageSettings = new BetterLocationMessageSettings();

			$markup = new Markup();
			$markup->inline_keyboard = [$location->generateDriveButtons($chatMessageSettings)];

			$editMessage->reply_markup = $markup;
			$editMessage->text = sprintf('<a href="%s">%s</a>', $location->getStaticMapUrl(), Icons::LOCATION) . $this->getText();
		} else {
			$editMessage->text = $this->getText() . ' (no location detected)';
		}

		$editMessage->disable_web_page_preview = false;
		$this->run($editMessage);

	}
}


