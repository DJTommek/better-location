<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Command;

use App\Config;
use App\Factory\UserFactory;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\IgnoreButton;
use App\TelegramCustomWrapper\TelegramHelper;
use App\User;
use unreal4u\TelegramAPI\Telegram;

class IgnoreCommand extends Command
{
	const CMD = '/ignore';
	const ICON = Icons::SETTINGS;
	const DESCRIPTION = 'Add sender to the ignore list';

	public function __construct(
		private readonly UserFactory $userFactory,
	) {
	}

	public function handleWebhookUpdate(): void
	{
		if ($this->isAdmin() === false) {
			return;
		}

		$chatType = $this->getChat()?->getEntity()->telegramChatType;
		if (in_array($chatType, Config::IGNORE_FILTER_ALLOWED_CHAT_TYPES, true) === false) {
			$this->reply(Icons::ERROR . ' This command can be used in only groups.');
			return;
		}

		if ($this->isTgMessageReply() === false) {
			$this->reply($this->helpMessage() . 'Run this command again as reply to some message and confirm adding user to the ignore list.');
			return;
		}

		$markup = new Telegram\Types\Inline\Keyboard\Markup();

		$detectedTgUsers = [];

		// Actual sender of marked message to this chat (always available)
		$replyFromTgUser = $this->getTgMessage()->reply_to_message->from;
		$detectedTgUsers[$replyFromTgUser->id] = $replyFromTgUser;

		// Actual creator of marked message (someone forwarded its message)
		// @TODO `forward_from` is obsolete since December 29, 2023 (Bot API 7.0), use `forward_origin`.
		// @see https://core.telegram.org/bots/api-changelog#december-29-2023
		$replyForwardFromTgUser = $this->getTgMessage()?->reply_to_message?->forward_from;
		if ($replyForwardFromTgUser !== null) {
			$detectedTgUsers[$replyForwardFromTgUser->id] = $replyForwardFromTgUser;
		}

		// Actual creator of the message is bot and message was sent to this chat via inline mode
		$replyViaBotTgUser =  $this->getTgMessage()?->reply_to_message?->via_bot;
		if ($replyViaBotTgUser !== null) {
			$detectedTgUsers[$replyViaBotTgUser->id] = $replyViaBotTgUser;
		}

		$buttonRows = [];
		foreach ($detectedTgUsers as $detectedTgUser) {
			assert($detectedTgUser instanceof Telegram\Types\User);
			$detectedUser = $this->userFactory->createOrRegisterFromTelegram(
				$detectedTgUser->id,
				TelegramHelper::getUserDisplayname($detectedTgUser),
			);

			$buttonRows[] = [
				$this->createIgnoreUserButton($detectedUser),
			];
		}
		$markup->inline_keyboard = $buttonRows;

		$text = $this->helpMessage() . 'Click on button below to add someone to the ignore list.';
		$this->reply($text, $markup);
	}

	private function helpMessage(): string
	{
		$text = Icons::IGNORE_FILTER . ' <b>Ignore filter</b> is feature, which limits which messages will be analyzed for potential locations. ';
		$text .= sprintf(
			'If user is on ignore list, @%s will simply ignore all messages shared by this user to this chat, even if it contains some location.',
			Config::TELEGRAM_BOT_NAME,
		);
		$text .= TelegramHelper::NEW_LINE;
		$text .= sprintf(
			'Ignore filter can be managed in <a href="%s" target="_blank">chat settings</a>.',
			$this->getChat()->getChatSettingsUrl(),
		);
		$text .= TelegramHelper::NEW_LINE;
		$text .= TelegramHelper::NEW_LINE;
		return $text;
	}

	private function createIgnoreUserButton(User $user): Telegram\Types\Inline\Keyboard\Button
	{
		$button = new Telegram\Types\Inline\Keyboard\Button();
		$button->text = $user->getTelegramDisplayname();
		$button->callback_data = sprintf(
			'%s %s %s %d',
			IgnoreButton::CMD,
			IgnoreButton::ACTION_SENDER,
			IgnoreButton::SUBACTION_ADD,
			$user->getId(),
		);
		return $button;
	}
}
