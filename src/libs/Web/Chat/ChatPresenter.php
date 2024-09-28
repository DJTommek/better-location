<?php declare(strict_types=1);

namespace App\Web\Chat;

use App\Address\AddressProvider;
use App\BetterLocation\ProcessExample;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\Chat;
use App\Factory\ChatFactory;
use App\Pluginer\Pluginer;
use App\Pluginer\PluginerException;
use App\Repository\ChatEntity;
use App\Repository\ChatMembersRepository;
use App\Repository\ChatRepository;
use App\Repository\UserEntity;
use App\Utils\Strict;
use App\Web\Flash;
use App\Web\MainPresenter;
use Nette\Http\UrlImmutable;
use Psr\Http\Client\ClientInterface;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram;

class ChatPresenter extends MainPresenter
{
	private int $chatTelegramId;
	private ?Chat $chat = null;
	private bool $isUserAdmin = false;

	public function __construct(
		private readonly ChatRepository $chatRepository,
		private readonly ChatMembersRepository $chatMembersRepository,
		private readonly ServicesManager $servicesManager,
		private readonly ClientInterface $httpClient,
		private readonly ProcessExample $processExample,
		private readonly AddressProvider $addressProvider,
		private readonly ChatFactory $chatFactory,
		ChatTemplate $template,
	) {
		$this->template = $template;
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

		// @TODO Chat() is requesting this data too, deduplicate this call somehow
		$chatEntity = $this->chatRepository->findByTelegramId($this->chatTelegramId);
		if ($chatEntity === null) {
			return;
		}

		if (!$this->chatMembersRepository->isAdmin($chatEntity->id, $this->user->getId())) {
			return;
		}
		$this->isUserAdmin = true;

		$this->chat = $this->chatFactory->create($chatEntity);

		// @TODO load info and set $this->>template->canBotEditMessagesOfOthers;

		$this->template->formPluginerUrl = $this->chat->getPluginerUrl()?->getAbsoluteUrl() ?? '';
		if ($this->isPostRequest()) {
			$this->handleSettingsForm();
		}
	}

	public function beforeRender(): void
	{
		if ($this->isUserAdmin === false) {
			$this->template->prepareError(requireAdmin: true);
			$this->setTemplateFilename('chatError.latte');
			return;
		}

		$this->template->telegramChatId = $this->chatTelegramId;
		$this->template->exampleInput = $this->processExample->getExampleInput();
		$exampleCollection = $this->processExample->getExampleCollection();

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

		$location = $exampleCollection->getFirst();
		if ($this->chat->settingsShowAddress() && $location->hasAddress() === false) {
			$location->setAddress($this->addressProvider->reverse($location)->getAddress());
		}

		$this->template->exampleLocation = $location;
		$this->template->chat = $this->chat;
		$this->template->prepareOk($this->tgChatFromEntity($this->chat->getEntity()), $this->servicesManager);

		$this->setTemplateFilename('chat.latte');
	}

	/**
	 * @return void|never
	 */
	private function handleSettingsForm()
	{
		$this->chat->settingsPreview(isset($_POST['map-preview']));
		$this->chat->settingsShowAddress(isset($_POST['show-address']));
		$this->chat->settingsTryLoadIngressPortal(isset($_POST['try-load-ingress-portal']));

		if (isset($_POST['output-type'])) {
			try {
				$this->chat->settingsOutputType((int)$_POST['output-type']);
			} catch (\InvalidArgumentException $exception) {
				$errorMessage = sprintf('Message output type is not valid: "%s"', htmlspecialchars($exception->getMessage()));
				$this->flashMessage($errorMessage, Flash::ERROR, null);
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
				$collection = $this->processExample->getExampleCollection();
				$pluginer = $this->pluginerFactory($url);
				try {
					$pluginer->process($collection);
				} catch (PluginerException $exception) {
					$errorMessage = sprintf('Pluginer URL is valid but error occured while testing it: "%s"', htmlspecialchars($exception->getMessage()));
					$this->flashMessage($errorMessage, Flash::ERROR, null);
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

		$services = $this->servicesManager->getServices();

		$linkServicesToSave = $this->getServicesFromPost($services, 'link-services-real');
		if ($linkServicesToSave !== null) {
			$this->chat->getMessageSettings()->setLinkServices($linkServicesToSave);
		}

		$buttonServicesToSave = $this->getServicesFromPost($services, 'button-services-real');
		if ($buttonServicesToSave !== null) {
			$this->chat->getMessageSettings()->setButtonServices($buttonServicesToSave);
		}

		$textServicesToSave = $this->getServicesFromPost($services, 'text-services-real');
		if ($textServicesToSave !== null) {
			$this->chat->getMessageSettings()->setTextServices($textServicesToSave);
		}

		$this->chat->getMessageSettings()->saveToDb($this->chat->getEntity()->id);
		$this->flashMessage('Settings was updated.');
		$this->redirect('/chat/' . $this->chatTelegramId);
	}

	private function pluginerFactory(UrlImmutable $url): Pluginer
	{
		return new Pluginer(
			httpClient: $this->httpClient,
			pluginUrl: $url,
			updateId: random_int(1_000_000, 9_999_999),
			messageId: random_int(1_000_000, 9_999_999),
			chat: $this->tgChatFromEntity($this->chat->getEntity()),
			user: $this->tgUserFromEntity($this->user->getEntity()),
		);
	}

	/**
	 * @param array<int,class-string<AbstractService>> $services
	 * @return array<int,class-string<AbstractService>>|null Null if requested data is not available in request. Empty
	 * array represents that user does not want any service to be selected.
	 */
	private function getServicesFromPost(array $services, string $postKey): ?array
	{
		$idsRaw = $this->request->getPost($postKey);
		if ($idsRaw === null) {
			return null;
		}
		if (trim($idsRaw) === '') {
			return [];
		}
		$ids = explode(',', $idsRaw);

		return array_filter(array_map(fn($id) => $services[$id] ?? null, $ids));
	}

	private function tgChatFromEntity(ChatEntity $chatEntity): Telegram\Types\Chat
	{
		$tgChat = new Telegram\Types\Chat();
		$tgChat->id = $this->chat->getEntity()->telegramId;
		$tgChat->type = $this->chat->getEntity()->telegramChatType;
		return $tgChat;
	}

	private function tgUserFromEntity(UserEntity $userEntity): Telegram\Types\User
	{
		$tgUser = new Telegram\Types\User();
		$tgUser->id = $userEntity->telegramId;
		$telegramDisplayname = $userEntity->telegramName;
		if (str_starts_with($telegramDisplayname, '@')) {
			$tgUser->username = mb_substr($telegramDisplayname, 1);
		} else {
			$tgUser->first_name = $telegramDisplayname;
		}
		return $tgUser;
	}
}

