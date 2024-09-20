<?php declare(strict_types=1);

namespace App\Web\ChatsList;

use App\Repository\ChatRepository;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Telegram;

class ChatsListPresenter extends MainPresenter
{
	public function __construct(
		private readonly ChatRepository $chatRepository,
		ChatsListTemplate $template,
	) {
		$this->template = $template;
	}

	public function action(): void
	{
		if ($this->login->isLogged() === false) {
			$this->renderForbidden();
		}

		$this->template->chats = $this->chatRepository->findByAdminId($this->user->getId());
	}

	public function beforeRender(): void
	{
		$this->setTemplateFilename('settings.chatsList.latte');
	}
}

