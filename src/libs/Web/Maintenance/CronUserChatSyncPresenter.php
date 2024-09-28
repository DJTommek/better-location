<?php declare(strict_types=1);

namespace App\Web\Maintenance;

use App\Database;
use App\Factory\UserFactory;
use App\Repository\UserEntity;
use App\Utils\Formatter;
use App\Web\MainPresenter;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

/**
 * Every registered Telegram user should have also registered private chat.
 * If this syncer creates private chat it means, that there is probably some fault in domain logic and should be
 * investigated and fixed. Originally this converter is meant to be used one-time only as migration.
 */
class CronUserChatSyncPresenter extends MainPresenter
{
	private const LOG_PREFIX = '[CRON UserChat syncer] ';

	public function __construct(
		private readonly Database $db,
		private readonly UserFactory $userFactory,
	) {
	}

	public function action(): void
	{
		if ($this->request->getQuery('password') !== \App\Config::CRON_PASSWORD) {
			$this->apiResponse(true, 'Invalid password', httpCode: self::HTTP_FORBIDDEN);
		}

		Debugger::timer(self::class);
		$this->db->beginTransaction();
		try {
			$desynchronizedUsersCount = $this->doLogic();
			$this->db->commit();
		} catch (\Throwable $exception) {
			$this->db->rollback();
			throw  $exception;
		}
		$elapsedSeconds = Debugger::timer(self::class);

		$resultMessage = sprintf('Fixed %d desynchronized chat-users in %s.', $desynchronizedUsersCount, Formatter::seconds($elapsedSeconds));
		Debugger::log(self::LOG_PREFIX . $resultMessage);
		$this->apiResponse(false, $resultMessage, ['elapsedSeconds' => $elapsedSeconds, 'desynchronizedUserChatsCount' => $desynchronizedUsersCount]);
	}

	private function doLogic(): int
	{
		// Select all users, that has user instance but not privat chat instance
		$query = $this->db->query('SELECT * FROM better_location_user WHERE user_telegram_id NOT IN (SELECT chat_telegram_id FROM better_location_chat)');
		$desynchronizedUsersCount = $query->rowCount();
		if ($desynchronizedUsersCount === 0) {
			$this->apiResponse(false, 'Every user has private chat instance.');
		}

		while ($row = $query->fetch(\PDO::FETCH_LAZY)) {
			$userEntity = UserEntity::fromRow($row);
			$this->userFactory->createOrRegisterFromTelegram(
				$userEntity->telegramId,
				$userEntity->telegramName,
			);

			$logMessage = sprintf(
				'Created private chat for user ID %d (TG ID %d)',
				$userEntity->id,
				$userEntity->telegramId,
			);
			Debugger::log(self::LOG_PREFIX . $logMessage, Debugger::WARNING);
		}
		return $desynchronizedUsersCount;
	}
}

