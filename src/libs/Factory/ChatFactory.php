<?php declare(strict_types=1);

namespace App\Factory;

use App\Chat;
use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\Repository\ChatUserRepository;
use App\TelegramCustomWrapper\ChatMemberRecalculator;
use unreal4u\TelegramAPI\Telegram;

final readonly class ChatFactory
{
	public function __construct(
		private ChatRepository $chatRepository,
		private ChatUserRepository $chatUserRepository,
		private ChatMemberRecalculator $chatMemberRecalculator,
	) {
	}

	public function create(ChatEntity $chatEntity): Chat
	{
		return new Chat(
			$this->chatRepository,
			$this->chatUserRepository,
			$chatEntity,
		);
	}

	/**
	 * @param \unreal4u\TelegramAPI\Telegram\Types\Chat::TYPE_* $telegramChatType
	 */
	public function createOrRegisterFromTelegram(
		int $telegramChatId,
		string $telegramChatType,
		string $telegramChatName,
	): Chat {
		$chatEntity = $this->chatRepository->findByTelegramId($telegramChatId);
		if ($chatEntity === null) {
			$this->chatRepository->insert($telegramChatId, $telegramChatType, $telegramChatName);
			$chatEntity = $this->chatRepository->findByTelegramId($telegramChatId);
			$this->chatMemberRecalculator->processOneChat($chatEntity);
		}
		assert($chatEntity instanceof ChatEntity);

		if ($telegramChatName !== $chatEntity->telegramName) { // Administrator has changed chat's name on Telegram
			$chatEntity->telegramName = $telegramChatName;
			$this->chatRepository->update($chatEntity);
		}

		return $this->create($chatEntity);
	}
}
