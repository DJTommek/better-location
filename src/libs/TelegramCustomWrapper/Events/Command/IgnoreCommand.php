<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\IgnoreButton;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

class IgnoreCommand extends Command
{
	const CMD = '/ignore';
	const ICON = Icons::SETTINGS;
	const DESCRIPTION = 'Add sender to the ignore list';

	public function handleWebhookUpdate(): void
	{
		if ($this->isAdmin() === false) {
			return;
		}

		if ($this->isTgPm()) {
			$this->reply(Icons::ERROR . ' This command can be used in only groups and channels.');
			return;
		}

		if ($this->isTgMessageReply() === false) {
			$this->reply(Icons::ERROR . ' This command can be used only as reply to specific message.');
			return;
		}

		$message = $this->getTgMessage();

		$markup = new Telegram\Types\Inline\Keyboard\Markup();

		$senderButton = $this->createIgnoreUserButton($this->getTgFrom());
		$markup->inline_keyboard[] = [$senderButton];

		$replyForwardFrom = $message->reply_to_message?->forward_from;
		if ($replyForwardFrom !== null) {
			$forwarderButton = $this->createIgnoreUserButton($replyForwardFrom);
			$markup->inline_keyboard[] = [$forwarderButton];
		}

		$text = sprintf('Add sender to the ignore list for this chat. Even if sender\'s messages will contain some location, @%s will ignore it.', Config::TELEGRAM_BOT_NAME);
		$this->reply($text, $markup);
	}

	private function createIgnoreUserButton(Telegram\Types\User $user): Telegram\Types\Inline\Keyboard\Button
	{
		$button = new Telegram\Types\Inline\Keyboard\Button();
		$button->text = sprintf(
			'Ignore user %s',
			TelegramHelper::getUserDisplayname($user),
		);
		$button->callback_data = sprintf(
			'%s %s %s %d',
			IgnoreButton::CMD,
			IgnoreButton::ACTION_ADD,
			IgnoreButton::ACTION_ADD_USER,
			$user->id
		);
		return $button;
	}
}
