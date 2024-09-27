<?php declare(strict_types=1);

namespace App\Web\ChatHistory;

use App\Repository\ChatEntity;
use App\Repository\ChatLocationHistoryRepository;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\Utils\Strict;
use App\Web\ChatErrorTrait;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;

class ChatHistoryPresenter extends MainPresenter
{
	private ?ChatEntity $chatEntity = null;

	public function __construct(
		private readonly ChatRepository $chatRepository,
		private readonly ChatLocationHistoryRepository $chatLocationHistoryRepository,
		private readonly TelegramCustomWrapper $telegramWrapper,
		ChatHistoryTemplate $template,
	) {
		$this->template = $template;
	}

	public function action(): void
	{
		if ($this->login->isLogged() === false) {
			return;
		}

		$telegramChatIdRaw = $_GET['telegramId'] ?? null;
		if ($telegramChatIdRaw === null || Strict::isInt($telegramChatIdRaw) === false) {
			return;
		}
		$telegramChatId = Strict::intval($telegramChatIdRaw);

		$chatMember = $this->loadUserChatMember($telegramChatId);
		if ($chatMember === null) {
			return;
		}

		$this->chatEntity = $this->chatRepository->findByTelegramId($telegramChatId);
		$chatHistory = $this->chatLocationHistoryRepository->findByTelegramChatId($telegramChatId);
		$this->template->prepareOk($this->chatEntity, $chatHistory);
	}

	private function loadUserChatMember(int|string $chatTelegramId): ?Telegram\Types\ChatMember
	{
		$getChatMember = new Telegram\Methods\GetChatMember();
		$getChatMember->chat_id = $chatTelegramId;
		$getChatMember->user_id = $this->login->getTelegramId();
		try {
			$chatMember = $this->telegramWrapper->run($getChatMember);
			assert($chatMember instanceof Telegram\Types\ChatMember);
			return $chatMember;
		} catch (ClientException $exception) {
			return null;
		}
	}

	public function beforeRender(): void
	{
		if ($this->chatEntity === null) {
			$this->template->prepareError(requireAdmin: false);
			$this->setTemplateFilename('chatError.latte');
			return;
		}

		$this->setTemplateFilename('chatHistory.latte');
	}
}

