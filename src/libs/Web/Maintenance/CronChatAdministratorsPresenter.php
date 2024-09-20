<?php declare(strict_types=1);

namespace App\Web\Maintenance;

use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\ChatMemberRecalculator;
use App\Utils\Formatter;
use App\Web\MainPresenter;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

/**
 * Iterate via every detected chat, load administrators and create links between internally stored Users and Chats.
 * Mapping only chat Administrators and Owners.
 */
class CronChatAdministratorsPresenter extends MainPresenter
{
	public function __construct(
		private readonly ChatRepository $chatRepository,
		private readonly ChatMemberRecalculator $chatMemberRecalculator,
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
			$chatsCounter = $this->chatMemberRecalculator->processAllChats();
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
}

