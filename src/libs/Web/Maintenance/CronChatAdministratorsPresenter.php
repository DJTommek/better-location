<?php declare(strict_types=1);

namespace App\Web\Maintenance;

use App\Database;
use App\Repository\ChatEntity;
use App\Repository\ChatMemberEntity;
use App\Repository\ChatMembersRepository;
use App\Repository\ChatRepository;
use App\Repository\Repository;
use App\Repository\UserEntity;
use App\Repository\UserRepository;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Formatter;
use App\Web\MainPresenter;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Custom\ChatMembersArray;

/**
 * Iterate via every detected chat, load administrators and create links between internally stored Users and Chats.
 * Mapping only chat Administrators and Owners.
 */
class CronChatAdministratorsPresenter extends MainPresenter
{
	public function __construct(
		private readonly TelegramCustomWrapper $telegramCustomWrapper,
		private readonly ChatRepository $chatRepository,
		private readonly UserRepository $userRepository,
		private readonly ChatMembersRepository $chatMembersRepository,
	) {
	}

	public function action(): void
	{
		if ($this->request->getQuery('password') !== \App\Config::CRON_PASSWORD) {
			$this->apiResponse(true, 'Invalid password', httpCode: self::HTTP_FORBIDDEN);
		}

		$this->chatRepository->db->beginTransaction();

		Debugger::timer(self::class);
		try {
			$chatsCounter = $this->processAllChats();
		} catch (\Throwable $exception) {
			$elapsedSeconds = Debugger::timer(self::class);
			$this->chatRepository->db->rollback();
			Debugger::log($exception, Debugger::EXCEPTION);
			$this->apiResponse(true, 'Exception occured, check logs for more details.', ['elapsedSeconds' => $elapsedSeconds], httpCode: self::HTTP_INTERNAL_SERVER_ERROR);
		}

		$elapsedSeconds = Debugger::timer(self::class);
		$resultMessage = sprintf('Processing chat administrators: processed %d chats in %s.', $chatsCounter, Formatter::seconds($elapsedSeconds));
		Debugger::log('[CRON] ' . $resultMessage, Debugger::DEBUG);
		$this->chatRepository->db->commit();
		$this->apiResponse(false, $resultMessage, ['elapsedSeconds' => $elapsedSeconds, 'chatsCounter' => $chatsCounter]);
	}

	private function processAllChats(): int
	{
		$counter = 0;
		foreach ($this->chatRepository->getAll() as $chat) {
			$membersCount = $this->processOneChat($chat);
			$counter += $membersCount;
		}
		return $counter;
	}

	/**
	 * Returns count of members
	 */
	private function processOneChat(ChatEntity $chat): int
	{
		if ($chat->telegramChatType === ChatEntity::CHAT_TYPE_PRIVATE) {
			return $this->processPrivateChat($chat);
		} else {
			return $this->processNonPrivateChat($chat);
		}
	}

	private function processPrivateChat(ChatEntity $chat): int
	{
		$this->chatMembersRepository->deleteByChatId($chat->id);
		$user = $this->getUserEntity($chat->telegramId, $chat->telegramName);
		$this->chatMembersRepository->insert($chat->id, $user->id, ChatMemberEntity::ROLE_CREATOR);
		return 1;
	}

	private function processNonPrivateChat(ChatEntity $chat): int
	{
		$getChatAdministrators = new Telegram\Methods\GetChatAdministrators();
		$getChatAdministrators->chat_id = $chat->telegramId;
		try {
			$chatAdministrators = $this->telegramCustomWrapper->run($getChatAdministrators);
			assert($chatAdministrators instanceof ChatMembersArray);
		} catch (ClientException $exception) {
			if ($this->isChatDeleted($exception)) {
				$this->handleChatDeleted($chat);
			} else if ($exception->getMessage() === TelegramHelper::UPGRADED_TO_SUPERGROUP) {
				$this->handleChatUpgraded($chat, $exception);
				// @TODO re-run for new Telegram chat ID
			} else {
				Debugger::log($exception, Debugger::EXCEPTION);
			}
			return 0;
		}

		// @TODO optimize by separating records to delete and records to insert
		$this->chatMembersRepository->deleteByChatId($chat->id);
		$counter = 0;
		foreach ($chatAdministrators as $chatAdministrator) {
			assert(
				$chatAdministrator instanceof Telegram\Types\ChatMember\ChatMemberAdministrator
				|| $chatAdministrator instanceof Telegram\Types\ChatMember\ChatMemberOwner,
			);
			$user = $this->getUserEntity($chatAdministrator->user->id, TelegramHelper::getUserDisplayname($chatAdministrator->user));

			$this->chatMembersRepository->insert($chat->id, $user->id, $chatAdministrator->status);
			$counter++;
		}

		return $counter;
	}

	private function isChatDeleted(ClientException $exception): bool
	{
		return in_array(
			$exception->getMessage(),
			[TelegramHelper::CHAT_GROUP_DELETED, TelegramHelper::CHAT_GROUP_KICKED, TelegramHelper::CHAT_SUPERGROUP_KICKED, TelegramHelper::CHAT_NOT_FOUND]
			, true,
		);
	}

	private function handleChatDeleted(ChatEntity $chat): void
	{
		$chat->status = Repository::DELETED;
		$this->chatRepository->update($chat);
	}

	private function handleChatUpgraded(ChatEntity $chat, ClientException $exception): void
	{
		try {
			$originalTelegramId = $chat->telegramId;
			$originalChatType = $chat->telegramChatType;

			$chat->telegramId = $exception->getError()->parameters->migrate_to_chat_id;
			$chat->telegramChatType = ChatEntity::CHAT_TYPE_SUPERGROUP;
			$this->chatRepository->update($chat);
		} catch (\PDOException $exception) {
			if ($exception->getCode() === Database::PDO_CODE_INTEGRITY_CONSTRAINT_VIOLATION) {
				// Invalid state, revert changes and mark this old chat as deleted
				Debugger::log(sprintf(
					'Unable to convert ID %d from group TG ID %s to supergroup TG ID %s - there is already record for new TG ID. Old chat was marked as deleted.',
					$chat->id,
					$originalTelegramId,
					$originalChatType,
				),
					Debugger::WARNING);
				$chat->telegramId = $originalTelegramId;
				$chat->telegramChatType = $originalChatType;
				$chat->status = Repository::DELETED;
				$this->chatRepository->update($chat);
				return;
			}
			throw $exception;
		}
	}

	private function getUserEntity(int $userTelegramId, string $userDisplayname): UserEntity
	{
		$user = $this->userRepository->fromTelegramId($userTelegramId);
		if ($user !== null) {
			return $user;
		}

		$this->userRepository->insert($userTelegramId, $userDisplayname);
		return $this->userRepository->fromTelegramId($userTelegramId);

	}
}

