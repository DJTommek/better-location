<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\BetterLocation\Service\WazeService;
use App\Chat;
use App\Config;
use App\Factory;
use App\Pluginer\Pluginer;
use App\Pluginer\PluginerException;
use App\Repository\ChatEntity;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Strict;
use App\Web\Flash;
use App\Web\MainPresenter;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\Telegram;

class ChatPresenter extends MainPresenter
{
	private int $chatTelegramId;
	private ?Telegram\Types\Chat $chatResponse = null;
	private ?Chat $chat = null;
	private ?Telegram\Types\ChatMember $chatMemberUser = null;
	public string $exampleInput = 'https://www.waze.com/ul?ll=50.087451%2C14.420671';
	private bool $isUserAdmin = false;

	public function __construct()
	{
		$this->template = new ChatTemplate();
		parent::__construct();
	}

	public function action(): void
	{
		if ($this->login->isLogged() === false) {
			return;
		}

		if (Strict::isInt($_GET['telegramId'] ?? null) === false) {
			return;
		}

		$this->chatTelegramId = Strict::intval($_GET['telegramId']);
		$this->loadChatData();

		if ($this->isUserAdmin === false) {
			return;
		}

		$this->chat = new Chat($this->chatTelegramId, $this->chatResponse->type, TelegramHelper::getChatDisplayname($this->chatResponse));
		$this->template->formPluginerUrl = $this->chat->getPluginerUrl()?->getAbsoluteUrl() ?? '';
		if ($this->isPostRequest()) {
			$this->handleSettingsForm();
		}
	}

	public function render(): void
	{
		if ($this->isUserAdmin === false) {
			$this->template->prepareError();
			Factory::latte('chatError.latte', $this->template);
			return;
		}

		$this->template->telegramChatId = $this->chatTelegramId;
		$this->template->exampleInput = $this->exampleInput;
		$exampleCollection = WazeService::processStatic($this->exampleInput)->getCollection();

		// Do not process Pluginer if it's POST request (form is being saved), validation is done there
		if ($this->isPostRequest() === false && $this->chat->getPluginerUrl() !== null) {
			$pluginer = $this->pluginerFactory($this->chat->getPluginerUrl());
			try {
				$pluginer->process($exampleCollection);
			} catch (PluginerException $exception) {
				$this->flashMessage(
					'Error while processing your Pluginer URL, check if your server is online and responding correctly.<br>' . htmlspecialchars($exception->getMessage()),
					Flash::ERROR,
					null,
				);
			} catch (\Exception $exception) {
				$this->flashMessage('BetterLocation server general error while processing your Pluginer URL, try again later.', Flash::ERROR, null);
				Debugger::log($exception, Debugger::EXCEPTION);
			}
		}

		$this->template->exampleLocation = $exampleCollection->getFirst();
		$this->template->prepareOk($this->chatResponse);
		$this->template->chat = $this->chat;
		Factory::latte('chat.latte', $this->template);
	}

	private function loadChatData(): void
	{
		try {
			$telegramWrapper = Factory::telegram();

			$userTgId = $this->user->getTelegramId();

			$getChat = new Telegram\Methods\GetChat();
			$getChat->chat_id = $this->chatTelegramId;
			$response = $telegramWrapper->run($getChat);
			assert($response instanceof Telegram\Types\Chat);
			$this->chatResponse = $response;

			if ($response->type === ChatEntity::CHAT_TYPE_PRIVATE) {
				$getChatMember = new Telegram\Methods\GetChatMember();
				$getChatMember->chat_id = $this->chatTelegramId;
				$getChatMember->user_id = $userTgId;
				$chatMember = $telegramWrapper->run($getChatMember);
				assert($chatMember instanceof Telegram\Types\ChatMember);
				if ($chatMember->user->id !== $userTgId) {
					throw new \RuntimeException('Invalid state - Telegram ID ID of logged user must match');
				}
				$this->isUserAdmin = true;
				$this->chatMemberUser = $chatMember;
			} else {
				$getchatAdmins = new Telegram\Methods\GetChatAdministrators();
				$getchatAdmins->chat_id = $this->chatTelegramId;
				$response = $telegramWrapper->run($getchatAdmins);
				assert($response instanceof Telegram\Types\Custom\ChatMembersArray);
				foreach ($response as $admin) {
					assert($admin instanceof Telegram\Types\ChatMember);
					if ($admin->user->id === $userTgId) {
						$this->isUserAdmin = true;
						$this->chatMemberUser = $admin;
					}
					if (
						$admin->user->username === Config::TELEGRAM_BOT_NAME
						&& $admin instanceof Telegram\Types\ChatMember\ChatMemberAdministrator
						&& $admin->can_edit_messages
					) {
						$this->template->canBotEditMessagesOfOthers = true;
					}
				}
			}

		} catch (ClientException $exception) {
			// do nothing, user probable just does not have permission
		}
	}

	private function handleSettingsForm()
	{
		$this->chat->settingsPreview(isset($_POST['map-preview']));
		$this->chat->settingsShowAddress(isset($_POST['show-address']));

		if (isset($_POST['output-type'])) {
			try {
				$this->chat->settingsOutputType((int)$_POST['output-type']);
			} catch (\InvalidArgumentException $exception) {
				$this->flashMessage(sprintf(
					'Message output type is not valid: "%s"',
					htmlspecialchars($exception->getMessage()),
				), Flash::ERROR, null);
				return;
			}
		}

		// Validate Pluginer URL
		if (isset($_POST['pluginer-url'])) {
			$this->template->formPluginerUrl = $_POST['pluginer-url'];
			try {
				$url = trim($_POST['pluginer-url']) === '' ? null : Strict::urlImmutable($_POST['pluginer-url']);
			} catch (\InvalidArgumentException $exception) {
				$this->flashMessage('Pluginer URL is not valid.', Flash::ERROR, null);
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
						htmlspecialchars($exception->getMessage()),
					), Flash::ERROR, null);
					return;
				} catch (\Exception $exception) {
					Debugger::log($exception, Debugger::EXCEPTION);
					$this->flashMessage('Pluginer URL is probably valid but BetterLocation server general error occured while testing it. Try again later.', Flash::ERROR, null);
					return;
				}
			}

			$this->chat->setPluginerUrl($url);
		}
		// Validate Pluginer URL - END

		$services = Factory::servicesManager()->getServices();

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
			user: $this->chatMemberUser->user,
		);
	}
}

