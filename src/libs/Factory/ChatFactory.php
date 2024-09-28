<?php declare(strict_types=1);

namespace App\Factory;

use App\Chat;
use App\Repository\ChatEntity;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\ChatMemberRecalculator;
use unreal4u\TelegramAPI\Telegram;

final readonly class ChatFactory
{
	public function __construct(
		private ChatRepository $chatRepository,
		private ChatMemberRecalculator $chatMemberRecalculator,
	) {
	}

	public function create(ChatEntity $chatEntity): Chat
	{
		return new Chat(
			$this->chatRepository,
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

		return new Chat(
			$this->chatRepository,
			$chatEntity,
		);
	}
}
