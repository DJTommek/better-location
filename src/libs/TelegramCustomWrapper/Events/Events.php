<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\BetterLocation\BetterLocationCollection;
use App\Chat;
use App\Config;
use App\Factory\ChatFactory;
use App\Factory\ProcessedMessageResultFactory;
use App\Factory\UserFactory;
use App\IngressLanchedRu\Client as IngressLanchedRuClient;
use App\Logger\CustomTelegramLogger;
use App\Pluginer\Pluginer;
use App\Repository\ChatRepository;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\ChatMemberRecalculator;
use App\TelegramCustomWrapper\SendMessage;
use App\TelegramCustomWrapper\TelegramHelper;
use App\User;
use App\Utils\DateImmutableUtils;
use App\Utils\SimpleLogger;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Http\UrlImmutable;
use Psr\Http\Client\ClientInterface;
use React\EventLoop\Factory;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\Abstracts\TelegramMethods;
use unreal4u\TelegramAPI\Abstracts\TelegramTypes;
use unreal4u\TelegramAPI\Exceptions\ClientException;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;

use function Clue\React\Block\await;

abstract class Events
{
	private readonly ChatRepository $chatRepository;
	private readonly ClientInterface $httpClient;
	private readonly ?IngressLanchedRuClient $lanchedRuClient;
	protected readonly ProcessedMessageResultFactory $processedMessageResultFactory;
	private readonly ChatMemberRecalculator $chatMemberRecalculator;
	private readonly ChatFactory $chatFactory;
	private readonly UserFactory $userFactory;

	protected readonly Update $update;
	private readonly TgLog $tgLog;
	/** @readonly */
	protected $loop;
	protected User $user;
	/**
	 * Might not be accessible for some situations (send via Telegram inline)
	 */
	protected ?Chat $chat = null;

	protected ?string $command = null;
	/**
	 * @var string[]
	 */
	protected array $params = [];

	/** Caching for method isAdmin() */
	private ?bool $isAdmin = null;

	abstract public function handleWebhookUpdate(): void;

	final public function setDependencies(
		ChatRepository $chatRepository,
		CustomTelegramLogger $customTelegramLogger,
		ClientInterface $httpClient,
		IngressLanchedRuClient $lanchedRuClient,
		ProcessedMessageResultFactory $processedMessageResultFactory,
		ChatMemberRecalculator $chatMemberRecalculator,
		ChatFactory $chatFactory,
		UserFactory $userFactory,
	): self {
		$this->chatRepository = $chatRepository;
		$this->httpClient = $httpClient;
		$this->lanchedRuClient = $lanchedRuClient;
		$this->processedMessageResultFactory = $processedMessageResultFactory;
		$this->chatMemberRecalculator = $chatMemberRecalculator;
		$this->chatFactory = $chatFactory;
		$this->userFactory = $userFactory;

		$this->loop = Factory::create();
		$this->tgLog = new TgLog(
			Config::TELEGRAM_BOT_TOKEN,
			new HttpClientRequestHandler($this->loop),
			$customTelegramLogger,
		);
		return $this;
	}

	final public function setUpdateObject(Update $update): self
	{
		$this->update = $update;

		$this->user = $this->userFactory->createOrRegisterFromTelegram(
			$this->getTgFromId(),
			$this->getTgFromDisplayname(),
		);

		// If message is forwarded, original sender must be registered in database too
		if ($this->isTgForward()) {
			$forwardFrom = TelegramHelper::getForwardFrom($update);
			$this->userFactory->createOrRegisterFromTelegram(
				$forwardFrom->id,
				TelegramHelper::getUserDisplayname($forwardFrom),
			);
		}

		if ($this->hasTgMessage()) {
			$this->chat = $this->chatFactory->createOrRegisterFromTelegram(
				$this->getTgChatId(),
				$this->getTgChat()->type,
				$this->getTgChatDisplayname(),
			);
		}

		if (
			TelegramHelper::myChatMember($update) === null
			&& TelegramHelper::isInlineQuery($update) === false
			&& TelegramHelper::isEdit($update) === false
		) {
			$this->command = TelegramHelper::getCommand($update);
			$this->params = TelegramHelper::getParams($update);
		}

		$this->afterInit();

		return $this;
	}

	protected function afterInit(): void
	{
		// Can be overriden
	}

	public function getTgUpdateId(): int
	{
		return $this->update->update_id;
	}

	public function getTgChat(): ?Telegram\Types\Chat
	{
		return $this->getTgMessage()?->chat;
	}

	public function getMessageSettings(): BetterLocationMessageSettings
	{
		if ($this->chat) {
			return $this->chat->getMessageSettings();
		} else {
			return $this->user->getMessageSettings();
		}
	}

	public function getPluginUrl(): ?UrlImmutable
	{
		if ($this->chat !== null) {
			return $this->chat->getEntity()->pluginUrl;
		}

		// Fallback, try to load user's private chat entity
		return $this->user->getPrivateChat()->getEntity()->pluginUrl;
	}

	public function getPluginer(): ?Pluginer
	{
		$pluginUrl = $this->getPluginUrl();
		if ($pluginUrl === null) {
			return null;
		}

		return new Pluginer(
			httpClient: $this->httpClient,
			pluginUrl: $pluginUrl,
			updateId: $this->getTgUpdateId(),
			messageId: $this->hasTgMessage() ? $this->getTgMessageId() : null,
			chat: $this->hasTgMessage() ? $this->getTgChat() : null,
			user: $this->getTgFrom(),
		);
	}

	protected function getIngressLanchedRuClient(): IngressLanchedRuClient
	{
		return $this->lanchedRuClient;
	}

	public function getTgFrom(): Telegram\Types\User|Telegram\Types\Chat
	{
		$tgMessage = $this->getTgMessage();
		return $tgMessage->from ?? $tgMessage->sender_chat;
	}

	public function getTgChatId(): int
	{
		return $this->getTgChat()->id;
	}

	public function getTgTopicId(): ?int
	{
		if ($this->isTgTopicMessage() === false) {
			return null;
		}
		return $this->getTgMessage()?->reply_to_message?->message_thread_id;
	}

	public function isTgTopicMessage(): bool
	{
		if ($this->hasTgMessage() === false) {
			return false;
		}

		return $this->getTgMessage()->is_topic_message === true;
	}

	public function isTgMessageReply(): bool
	{
		$reply = $this->getTgMessage()->reply_to_message;
		if ($reply !== null) {
			assert($reply instanceof Telegram\Types\Message);
			return true;
		}
		return false;
	}

	public function getTgFromId(): int
	{
		return $this->getTgFrom()->id;
	}

	public function getTgFromDisplayname(): string
	{
		$tgFrom = $this->getTgFrom();
		if ($tgFrom instanceof Telegram\Types\User) {
			return TelegramHelper::getUserDisplayname($tgFrom);
		}
		if ($tgFrom instanceof Telegram\Types\Chat) {
			return TelegramHelper::getChatDisplayname($tgFrom);
		}
	}

	public function getTgChatDisplayname(): string
	{
		return TelegramHelper::getChatDisplayname($this->getTgChat());
	}

	public function getTgMessageId(): int
	{
		return $this->getTgMessage()->message_id;
	}

	public function getTgText(): string
	{
		return $this->getTgMessage()->text;
	}

	public function getTgMessageSentDate(): ?\DateTimeImmutable
	{
		if ($this->hasTgMessage() === false) {
			return null;
		}

		$tgMessage = $this->getTgMessage();
		assert($tgMessage->date !== null);
		assert($tgMessage->date !== 0);
		return DateImmutableUtils::fromTimestamp($tgMessage->date);
	}

	abstract public function getTgMessage(): Telegram\Types\Message;

	/** @return bool overridden with false, where Telegram\Types\Message is not available */
	public function hasTgMessage(): bool
	{
		return true;
	}

	/** @return bool|null null if unknown (eg. clicked on button in via_bot message) */
	public function isTgPm(): ?bool
	{
		return TelegramHelper::isPM($this->update);
	}

	public function isTgChannelPost(): bool
	{
		return TelegramHelper::isChannelPost($this->update);
	}

	public function isTgForward(): bool
	{
		return TelegramHelper::isForward($this->update);
	}

	/**
	 * @return true|null
	 *  - true: if action was successfully send
	 *  - null: if is unable to send action but error is whitelisted
	 * @throws ClientException if is unable to send action and error is NOT whitelisted
	 * @TODO Check if action string is valid
	 */
	public function sendAction(string $action = TelegramHelper::CHAT_ACTION_TYPING): ?bool
	{
		$chatAction = new SendChatAction();
		$chatAction->chat_id = $this->getTgChatId();
		$chatAction->action = $action;
		$result = $this->runSmart($chatAction);
		if ($result === null) {
			return null;
		}
		assert($result instanceof Telegram\Types\Custom\ResultBoolean);
		assert($result->data === true);
		return $result->data;
	}

	/** Send message as reply to recieved message */
	public function reply(string $text, ?Markup $markup = null, array $options = []): ?Telegram\Types\Message
	{
		$msg = new SendMessage($this->getTgChatId(), $text, $this->getTgMessageId());
		if ($markup) {
			$msg->setReplyMarkup($markup);
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disableWebPagePreview($options['disable_web_page_preview']);
		}
		/** @var Telegram\Types\Message $response It always should be this type. Other should throw exception */
		$response = $this->runSmart($msg->msg);
		return $response;
	}

	public function replyLocation(CoordinatesInterface $location, ?Markup $markup = null): ?Telegram\Types\Message
	{
		$locationMessage = new Telegram\Methods\SendLocation();
		$locationMessage->chat_id = $this->getTgChatId();
		$locationMessage->latitude = $location->getLat();
		$locationMessage->longitude = $location->getLon();
		$locationMessage->reply_to_message_id = $this->getTgMessageId();
		$locationMessage->reply_markup = $markup;
		$response = $this->runSmart($locationMessage);
		assert($response === null || $response instanceof Telegram\Types\Message);
		return $response;
	}

	public function replyVenue(
		CoordinatesInterface $location,
		string $title,
		string $address,
		?Markup $markup = null,
	): ?Telegram\Types\Message {
		assert(trim($title) !== '' && trim($address) !== '');
		$venueMessage = new Telegram\Methods\SendVenue();
		$venueMessage->chat_id = $this->getTgChatId();
		$venueMessage->latitude = $location->getLat();
		$venueMessage->longitude = $location->getLon();
		$venueMessage->reply_to_message_id = $this->getTgMessageId();
		$venueMessage->reply_markup = $markup;
		$venueMessage->title = $title;
		$venueMessage->address = $address;
		$response = $this->runSmart($venueMessage);
		assert($response === null || $response instanceof Telegram\Types\Message);
		return $response;
	}

	/**
	 * @return ?TelegramTypes null if whitelisted exception
	 * @throws ClientException|\Exception
	 */
	public function runSmart(TelegramMethods $objectToSend): ?TelegramTypes
	{
		try {
			// Object must be cloned before sending it, because API wrapper adjusts some data
			$objectToSend2 = clone $objectToSend;
			return $this->run($objectToSend);
		} catch (ClientException $exception) {
			if (
				$exception->getMessage() === TelegramHelper::UPGRADED_TO_SUPERGROUP
				&& isset($objectToSend2->chat_id)
			) {
				assert($objectToSend2->chat_id !== '' && $objectToSend2->chat_id !== 0);
				$newChatId = $exception->getError()?->parameters?->migrate_to_chat_id ?? 0;
				if ($newChatId !== 0) {
					$objectToSend2->chat_id = $newChatId;
					return $this->run($objectToSend2);
				}
			}

			$ignoredExceptions = [
				TelegramHelper::NOT_CHANGED,
				TelegramHelper::TOO_OLD,
				TelegramHelper::MESSAGE_TO_EDIT_DELETED,
				TelegramHelper::CHAT_WRITE_FORBIDDEN,
				TelegramHelper::CHANNEL_WRITE_FORBIDDEN,
				TelegramHelper::REPLIED_MESSAGE_NOT_FOUND,
				TelegramHelper::BOT_BLOCKED_BY_USER,
				TelegramHelper::NOT_ENOUGH_RIGHTS_SEND_TEXT,
			];
			if (in_array($exception->getMessage(), $ignoredExceptions, true) === false) {
				Debugger::log(sprintf('TG API Command request error: "%s"', $exception->getMessage()), ILogger::EXCEPTION);
				Debugger::log($exception, ILogger::EXCEPTION);
				throw $exception;
			}
		}
		return null;
	}

	public function run(TelegramMethods $objectToSend): TelegramTypes
	{
		SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT, $objectToSend);
		try {
			$response = await($this->tgLog->performApiRequest($objectToSend), $this->loop);
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $response);
			return $response;
		} catch (ClientException $exception) {
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $exception->getMessage());
			throw $exception;
		}
	}

	protected function isAdmin(): bool
	{
		if ($this->isAdmin === null) {
			if ($this->isTgPm() || $this->isTgChannelPost()) {
				$this->isAdmin = true;
			} else {
				$getChatMember = new Telegram\Methods\GetChatMember();
				$getChatMember->user_id = $this->getTgFromId();
				$getChatMember->chat_id = $this->getTgChatId();
				$chatMember = $this->runSmart($getChatMember);
				if ($chatMember instanceof Telegram\Types\ChatMember === false) {
					throw new \LogicException(sprintf('Unexpected type "%s" returned from getChatMember(), chat_id = "%s", user_id = "%s"',
						get_class($chatMember),
						$this->getTgChatId(),
						$this->getTgFromId()),
					);
				}
				$this->isAdmin = TelegramHelper::isAdmin($chatMember);
			}
		}
		return $this->isAdmin;
	}

	final protected function recalculateChatMembers(): void
	{
		try {
			$chatEntity = $this->getChat()?->getEntity()
				?? $this->chatRepository->findByTelegramId($this->getTgChatId());
			if ($chatEntity === null) {
				return;
			}
			$this->chatMemberRecalculator->processOneChat($chatEntity);
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	/**
	 * If event detected some location, it is saved here into collection.
	 * If event is not supporting location output, it will return null
	 */
	public function getCollection(): ?BetterLocationCollection
	{
		return null;
	}

	public function getChat(): ?Chat
	{
		return $this->chat;
	}

	public function getUser(): User
	{
		return $this->user;
	}

	public function getUpdate(): Update
	{
		return $this->update;
	}
}
