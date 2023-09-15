<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Special;

use App\Config;
use App\TelegramCustomWrapper\SendMessage;
use App\TelegramCustomWrapper\TelegramHelper as TH;
use unreal4u\TelegramAPI\Telegram;

/**
 * Bot member status in some chat has been changed.
 */
class MyChatMemberEvent extends Special
{
	public function getTgFrom(): Telegram\Types\User
	{
		return $this->update->my_chat_member->from;
	}

	public function getTgChat(): Telegram\Types\Chat
	{
		return $this->update->my_chat_member->chat;
	}

	public function hasTgMessage(): bool
	{
		return false;
	}

	public function handleWebhookUpdate(): void
	{
		$chat = $this->getTgChat();
		$old = $this->update->my_chat_member->old_chat_member;
		$new = $this->update->my_chat_member->new_chat_member;
		if (
			$chat->type === Telegram\Types\Chat::TYPE_CHANNEL
			&& $old instanceof Telegram\Types\ChatMember\ChatMemberAdministrator === false
			&& $new instanceof Telegram\Types\ChatMember\ChatMemberAdministrator === true
		) {
			$this->handleAddedToChannel();
		}
	}

	private function handleAddedToChannel(): void
	{
		$admin = $this->getTgFrom();
		$chat = $this->getTgChat();

		$adminDisplayname = TH::getUserDisplayname($admin);
		$channelDisplayname = TH::getChatDisplayname($chat);
		$chatSettingsUrl = Config::getAppUrl('/chat/' . $this->getTgChatId());

		$text = sprintf(
				'Hi <b>%s</b>, thanks for adding me to channel <b>%s</b>.',
				$adminDisplayname,
				$channelDisplayname,
			) . TH::NL;
		$text .= 'I will be checking every post if it contains any form of location (coordinates, links, photos with EXIF...) and send a nicely formatted message.' . TH::NL;
		$text .= TH::NL;
		$text .= sprintf(
				'To update settings use <a href="%s" target="_blank">website</a> (commands in channels are ignored).',
				$chatSettingsUrl,
			);

		$replyMarkup = new Telegram\Types\Inline\Keyboard\Markup();
		$replyMarkup->inline_keyboard[] = [
			TH::loginUrlButton('Open settings', $chatSettingsUrl),
		];

		$sendMessage = new SendMessage(
			chatId: $admin->id,
			text: $text,
			replyMarkup: $replyMarkup,
		);
		$this->run($sendMessage->msg);
	}
}


