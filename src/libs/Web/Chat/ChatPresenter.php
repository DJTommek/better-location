<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\Service\WazeService;
use App\Chat;
use App\Factory;
use App\Pluginer\Pluginer;
use App\Pluginer\PluginerException;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Strict;
use App\Web\FlashMessage;
use App\Web\MainPresenter;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;

class ChatPresenter extends MainPresenter
{
	private $chatTelegramId;
	/** @var ?Telegram\Types\Chat */
	private $chatResponse;
	/** @var ?Chat */
	private $chat;
	/** @var ?Telegram\Types\ChatMember */
	private $chatMemberResponse;
	public string $exampleInput = 'https://www.waze.com/ul?ll=50.087451%2C14.420671';

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
		if ($this->isAdmin()) {
			$this->chat = new Chat($this->chatTelegramId, $this->chatResponse->type, TelegramHelper::getChatDisplayname($this->chatResponse));
			$this->template->formPluginerUrl = $this->chat->getPluginerUrl()?->getAbsoluteUrl() ?? '';
			if ($this->isPostRequest()) {
				$this->handleSettingsForm();
			}
		}
	}

	public function render(): void
	{
		$this->template->telegramChatId = $this->chatTelegramId;
		if ($this->isAdmin()) {
			$this->template->exampleInput = $this->exampleInput;
			$exampleCollection = WazeService::processStatic($this->exampleInput)->getCollection();

			// Do not process Pluginer if it's POST request (form is being saved), validation is done there
			if ($this->isPostRequest() === false && $this->chat->getPluginerUrl() !== null) {
				$pluginer = $this->pluginerFactory($this->chat->getPluginerUrl());
				try {
					$pluginer->process($exampleCollection);
				} catch (PluginerException $exception) {
					$this->flashMessage(
						'Error while processing your Pluginer URL, check if your server is online and responding correctly.<br>' . $exception->getMessage(),
						FlashMessage::FLASH_ERROR,
						null
					);
				} catch (\Exception $exception) {
					$this->flashMessage('BetterLocation server general error while processing your Pluginer URL, try again later.', FlashMessage::FLASH_ERROR, null);
					Debugger::log($exception, Debugger::EXCEPTION);
				}
			}

			$this->template->exampleLocation = $exampleCollection->getFirst();
			$this->template->prepareOk($this->chatResponse);
			$this->template->chat = $this->chat;
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

	private function handleSettingsForm()
	{
		$this->chat->settingsPreview(isset($_POST['map-preview']));
		$this->chat->settingsShowAddress(isset($_POST['show-address']));

		// Validate Pluginer URL
		if (isset($_POST['pluginer-url'])) {
			$this->template->formPluginerUrl = $_POST['pluginer-url'];
			try {
				$url = trim($_POST['pluginer-url']) === '' ? null : Strict::urlImmutable($_POST['pluginer-url']);
			} catch (\InvalidArgumentException $exception) {
				$this->flashMessage('Pluginer URL is not valid.', FlashMessage::FLASH_ERROR, null);
				return;
			}

			if ($url !== null) {
				$collection = WazeService::processStatic($this->exampleInput)->getCollection();
				$pluginer = $this->pluginerFactory($url);
				try {
					$pluginer->process($collection);
				} catch (PluginerException $exception) {
					$this->flashMessage(sprintf(
						'Pluginer URL is valid but error occured while testing it: "%s"',
						$exception->getMessage()
					), FlashMessage::FLASH_ERROR, null);
					return;
				} catch (\Exception $exception) {
					Debugger::log($exception, Debugger::EXCEPTION);
					$this->flashMessage('Pluginer URL is probably valid but BetterLocation server general error occured while testing it. Try again later.', FlashMessage::FLASH_ERROR, null);
					return;
				}
			}

			$this->chat->setPluginerUrl($url);
		}
		// Validate Pluginer URL - END

		$services = Factory::ServicesManager()->getServices();

		$linkServicesToSave = [];
		foreach ($_POST['link-services'] ?? [] as $linkserviceId) {
			$linkServicesToSave[$linkserviceId] = $services[$linkserviceId];
		}
		$this->chat->getMessageSettings()->setLinkServices($linkServicesToSave);

		$buttonServicesToSave = [];
		foreach ($_POST['button-services'] ?? [] as $buttonService) {
			$buttonServicesToSave[$buttonService] = $services[$buttonService];
		}
		$this->chat->getMessageSettings()->setButtonServices($buttonServicesToSave);

		$textServicesToSave = [];
		foreach ($_POST['text-services'] ?? [] as $textServiceId) {
			$textServicesToSave[$textServiceId] = $services[$textServiceId];
		}
		$this->chat->getMessageSettings()->setTextServices($textServicesToSave);

		$this->chat->getMessageSettings()->saveToDb($this->chat->getEntity()->id);
		$this->flashMessage('Settings was updated.');
		$this->redirect('/chat/' . $this->chatTelegramId);
	}

	private function pluginerFactory(UrlImmutable $url): Pluginer
	{
		return new Pluginer(
			pluginUrl: $url,
			updateId: random_int(1_000_000, 9_999_999),
			messageId: random_int(1_000_000, 9_999_999),
			chat: $this->chatResponse,
			user: $this->chatMemberResponse->user
		);
	}
}

