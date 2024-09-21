<?php declare(strict_types=1);

namespace App\Web\ChatsList;

use App\Repository\ChatMembersRepository;
use App\Repository\ChatRepository;
use App\Web\MainPresenter;
use Nette\Utils\Json;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

class ChatsListPresenter extends MainPresenter
{
	public function __construct(
		private readonly ChatRepository $chatRepository,
		private readonly ChatMembersRepository $chatMembersRepository,
		ChatsListTemplate $template,
	) {
		$this->template = $template;
	}

	public function action(): void
	{
		if ($this->isPostRequest()) {
			try {
				if (!$this->login->isLogged()) {
					$this->apiResponse(true, 'You are not logged in.', httpCode: self::HTTP_BAD_REQUEST);
				}
				try {
					$body = Json::decode($this->request->getRawBody());
				} catch (\Throwable) {
					$this->apiResponse(true, 'Invalid request body.', httpCode: self::HTTP_BAD_REQUEST);
				}

				if (!isset($body->chatId, $body->newValue, $body->name)) {
					$this->apiResponse(true, 'Invalid request body.', httpCode: self::HTTP_BAD_REQUEST);
				}

				if (!$this->chatMembersRepository->isAdmin($body->chatId, $this->user->getId())) {
					$this->apiResponse(true, 'Chat does not exists or you don\'t have permission.', httpCode: self::HTTP_FORBIDDEN);
				}

				$allowedProperties = ['settingsPreview', 'settingsShowAddress', 'settingsTryLoadIngressPortal'];
				$propertyName = $body->name;
				if (!in_array($propertyName, $allowedProperties, true)) {
					$this->apiResponse(true, 'Invalid property name.', httpCode: self::HTTP_BAD_REQUEST);
				}

				$chatEntity = $this->chatRepository->getById($body->chatId);
				try {
					$chatEntity->{$propertyName} = $body->newValue;
				} catch (\Throwable $exception) {
					$this->apiResponse(true, 'Invalid property value.', httpCode: self::HTTP_BAD_REQUEST);
				}
				$this->chatRepository->update($chatEntity);

				$this->apiResponse(false, 'Changed', [
					'name' => $propertyName,
					'newValue' => $chatEntity->{$propertyName},
				]);
			} catch (\Throwable $exception) {
				$errorMessage = Debugger::isEnabled() ? $exception->getMessage() : 'Error while updating chat settings. Contact administrator or try again later.';
				$this->apiResponse(true, $errorMessage, httpCode: self::HTTP_INTERNAL_SERVER_ERROR);
			}
		}

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

