<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events\Button;

use App\Icons;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\Events\Command\IgnoreCommand;
use App\TelegramCustomWrapper\TelegramHelper;

class IgnoreButton extends Button
{
	const CMD = IgnoreCommand::CMD;

	const ACTION_ADD = 'add';
	const ACTION_ADD_USER = 'user';

	public function __construct(private readonly ChatRepository $chatRepository) { }

	public function handleWebhookUpdate(): void
	{
		$params = TelegramHelper::getParams($this->update);
		$action = array_shift($params);

		match ($action) {
			self::ACTION_ADD => $this->actionAddUser(array_shift($params), array_shift($params)),
			default => $this->replyInvalidButton(),
		};
	}

	private function actionAddUser(?string $subAction, ?string $userId): void
	{
		if (
			$subAction !== self::ACTION_ADD_USER
			|| $userId === null
			|| is_numeric($userId) === false
		) {
			$this->replyInvalidButton();
			return;
		}

		$chatEntity = $this->chat->getEntity();
		$chatEntity->ignoreFilterParams->addTelegramSender((int)$userId);
		$this->chatRepository->update($chatEntity);

		$this->flash('User was added to the ignore list for this chat.', true);
	}

	private function replyInvalidButton(): void
	{
		$this->flash(sprintf('%s This button (ignore) is invalid.%sIf you believe that this is error, please contact admin', Icons::ERROR, TelegramHelper::NL), true);
	}
}
