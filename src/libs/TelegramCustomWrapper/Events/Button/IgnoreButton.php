<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Config;
use App\Icons;
use App\Repository\UserRepository;
use App\TelegramCustomWrapper\Events\Command\IgnoreCommand;
use App\TelegramCustomWrapper\TelegramHelper;
use unreal4u\TelegramAPI\Telegram;

class IgnoreButton extends Button
{
	const CMD = IgnoreCommand::CMD;

	const ACTION_SENDER = 'sender';

	const SUBACTION_ADD = 'add';

	public function __construct(private readonly UserRepository $userRepository) { }

	public function handleWebhookUpdate(): void
	{
		if ($this->isAdmin() === false) {
			$this->flash(sprintf('%s You are not admin of this chat.', Icons::ERROR), true);
			return;
		}

		$chatType = $this->getChat()?->getEntity()->telegramChatType;
		if (in_array($chatType, Config::IGNORE_FILTER_ALLOWED_CHAT_TYPES, true) === false) {
			$this->replyInvalidButton();
			return;
		}

		$params = TelegramHelper::getParams($this->update);
		$action = array_shift($params);

		match ($action) {
			self::ACTION_SENDER => $this->actionSender(array_shift($params), array_shift($params)),
			default => $this->replyInvalidButton(),
		};
	}

	private function actionSender(?string $subAction, ?string $userIdToIgnoreStr): void
	{
		if ($subAction !== self::SUBACTION_ADD || is_numeric($userIdToIgnoreStr) === false) {
			$this->replyInvalidButton();
			return;
		}

		$userIdToIgnore = (int)$userIdToIgnoreStr;
		$userToIgnore = $this->userRepository->findById($userIdToIgnore);
		if ($userToIgnore === null) {
			$this->flash('This user does not exists or cannot be ignored.', true);
			return;
		}

		$this->getChat()->addSenderToIgnoreFilter($userToIgnore->id);

		$this->flash(sprintf('User "%s" was added to the ignore list for this chat.', $userToIgnore->telegramName), true);
	}

	private function replyInvalidButton(): void
	{
		$this->flash(sprintf('%s This button (ignore) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, TelegramHelper::NL), true);
	}
}
