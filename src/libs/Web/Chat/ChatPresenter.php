<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\Chat;
use App\Factory;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\TelegramCustomWrapper;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Strict;
use App\Web\MainPresenter;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;

class ChatPresenter extends MainPresenter
{
	private $chatTelegramId;
	/** @var ?Telegram\Types\Chat */
	private $chatResponse;
	/** @var ?Telegram\Types\ChatMember */
	private $chatMemberResponse;


	public function __construct()
	{
		$this->template = new ChatTemplate();
		parent::__construct();
	}

	public function action()
	{
		if ($this->login->isLogged() && Strict::isInt($_GET['telegramId'] ?? null)) {
			$this->chatTelegramId = Strict::intval($_GET['telegramId']);
			$this->loadChatData();
		}
	}

	public function render(): void
	{
		$this->template->telegramChatId = $this->chatTelegramId;
		if ($this->isAdmin()) {
			$this->template->prepareOk($this->chatResponse);
			$this->template->chat = new Chat($this->chatTelegramId, $this->chatResponse->type, TelegramHelper::getChatDisplayname($this->chatResponse));
			$this->template->messageSettings = BetterLocationMessageSettings::loadByChatId($this->template->chat->getId());
			Factory::Latte('chat.latte', $this->template);
		} else {
			$this->template->prepareError();
			Factory::Latte('chatError.latte', $this->template);
		}
	}

	private function loadChatData(): void
	{
		try {
			$telegramWrapper = Factory::Telegram();

			$getChat = new Telegram\Methods\GetChat();
			$getChat->chat_id = $this->chatTelegramId;
			$this->chatResponse = $telegramWrapper->run($getChat);

			$getChatMember = new Telegram\Methods\GetChatMember();
			$getChatMember->chat_id = $this->chatTelegramId;
			$getChatMember->user_id = $this->user->getTelegramId();
			$this->chatMemberResponse = $telegramWrapper->run($getChatMember);
		} catch (ClientException $exception) {
			// do nothing, user probable just does not have permission
		}
	}

	/** Is administrator or it is PM chat */
	private function isAdmin(): bool
	{
		if ($this->chatResponse && $this->chatMemberResponse) {
			// @TODO optimize by not loading getChatMember, if chat type is private
			return ($this->chatResponse->type === 'private' || TelegramHelper::isAdmin($this->chatMemberResponse));
		}
		return false;
	}

}

