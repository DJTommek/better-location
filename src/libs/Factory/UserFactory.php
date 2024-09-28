<?php declare(strict_types=1);

namespace App\Factory;

use App\Repository\FavouritesRepository;
use App\Repository\UserEntity;
use App\Repository\UserRepository;
use App\User;
use unreal4u\TelegramAPI\Telegram;

final readonly class UserFactory
{
	public function __construct(
		private UserRepository $userRepository,
		private FavouritesRepository $favouritesRepository,
		private ChatFactory $chatFactory,
	) {
	}

	/**
	 * Telegram user ID always matches with user's private Telegram chat ID.
	 */
	public function createOrRegisterFromTelegram(
		int $telegramUserId,
		string $telegramUserDisplayname,
	): User {
		$userEntity = $this->userRepository->findByTelegramId($telegramUserId);
		if ($userEntity === null) {
			$this->userRepository->insert($telegramUserId, $telegramUserDisplayname);
			$userEntity = $this->userRepository->findByTelegramId($telegramUserId);
		}
		assert($userEntity instanceof UserEntity);

		// Every user has also private chat with identical Telegram ID, so if not exists, must be created.
		$chat = $this->chatFactory->createOrRegisterFromTelegram(
			telegramChatId: $telegramUserId,
			telegramChatType: Telegram\Types\Chat::TYPE_PRIVATE,
			telegramChatName: $telegramUserDisplayname,
		);

		return new User(
			$this->userRepository,
			$this->favouritesRepository,
			$userEntity,
			$chat,
		);
	}
}
