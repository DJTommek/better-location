<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\Chat;
use App\Config;
use App\Pluginer\Pluginer;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\SendMessage;
use App\TelegramCustomWrapper\TelegramHelper;
use App\User;
use App\Utils\Coordinates;
use App\Utils\SimpleLogger;
use Nette\Http\UrlImmutable;
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
	protected Update $update;
	private TgLog $tgLog;
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

	abstract public function handleWebhookUpdate();

	public function __construct(Update $update)
	{
		$this->update = $update;

		$this->loop = Factory::create();
		$this->tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($this->loop));
		$this->user = new User($this->getTgFromId(), $this->getTgFromDisplayname());
		$this->user->touchLastUpdate();
		if ($this->hasTgMessage()) {
			$this->chat = new Chat(
				$this->getTgChatId(),
				$this->getTgChat()->type,
				$this->getTgChatDisplayname()
			);
			$this->chat->touchLastUpdate();
		}

		if (TelegramHelper::isInlineQuery($update) === false && TelegramHelper::isEdit($update) === false) {
			$this->command = TelegramHelper::getCommand($update);
			$this->params = TelegramHelper::getParams($update);
		}
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
		return $this->user->getPrivateChatEntity()->pluginUrl;
	}

	public function getPluginer(): ?Pluginer
	{
		$pluginUrl = $this->getPluginUrl();
		if ($pluginUrl === null) {
			return null;
		}

		return new Pluginer(
			pluginUrl: $pluginUrl,
			updateId: $this->getTgUpdateId(),
			messageId: $this->hasTgMessage() ? $this->getTgMessageId() : null,
			chat: $this->hasTgMessage() ? $this->getTgChat() : null,
			user: $this->getTgFrom(),
		);
	}

	public function getTgFrom(): Telegram\Types\User
	{
		return $this->getTgMessage()->from;
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

	public function getTgFromId(): int
	{
		return $this->getTgFrom()->id;
	}

	public function getTgFromDisplayname(): string
	{
		return TelegramHelper::getUserDisplayname($this->getTgFrom());
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

	public function isTgForward()
	{
		return TelegramHelper::isForward($this->update);
	}

	/**
	 * @param string $action
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @TODO Check if action string is valid
	 */
	public function sendAction(string $action = TelegramHelper::CHAT_ACTION_TYPING)
	{
		$chatAction = new SendChatAction();
		$chatAction->chat_id = $this->getTgChatId();
		$chatAction->action = $action;
		$this->run($chatAction);
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
		$response = $this->run($msg->msg);
		return $response;
	}

	/** @param BetterLocation|Coordinates */
	public function replyLocation($location, ?Markup $markup = null): ?TelegramTypes
	{
		if ($location instanceof BetterLocation === false && $location instanceof Coordinates === false) {
			throw new \InvalidArgumentException();
		}

		$locationMessage = new Telegram\Methods\SendLocation();
		$locationMessage->chat_id = $this->getTgChatId();
		$locationMessage->latitude = $location->getLat();
		$locationMessage->longitude = $location->getLon();
		$locationMessage->reply_to_message_id = $this->getTgMessageId();
		$locationMessage->reply_markup = $markup;
		return $this->run($locationMessage);
	}

	/**
	 * @return ?TelegramTypes null if whitelisted exception
	 * @throws ClientException|\Exception
	 */
	public function run(TelegramMethods $objectToSend): ?TelegramTypes
	{
		SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT, $objectToSend);
		try {
			$response = await($this->tgLog->performApiRequest($objectToSend), $this->loop);
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $response);
			return $response;
		} catch (ClientException $exception) {
			SimpleLogger::log(SimpleLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $exception->getMessage());
			$ignoredExceptions = [
				TelegramHelper::NOT_CHANGED,
				TelegramHelper::TOO_OLD,
				TelegramHelper::MESSAGE_TO_EDIT_DELETED,
				TelegramHelper::CHAT_WRITE_FORBIDDEN,
				TelegramHelper::REPLIED_MESSAGE_NOT_FOUND,
				TelegramHelper::BOT_BLOCKED_BY_USER,
			];
			if (in_array($exception->getMessage(), $ignoredExceptions, true) === false) {
				Debugger::log(sprintf('TG API Command request error: "%s"', $exception->getMessage()), ILogger::EXCEPTION);
				Debugger::log($exception, ILogger::EXCEPTION);
				throw $exception;
			}
		}
		return null;
	}

	protected function isAdmin(): bool
	{
		if ($this->isAdmin === null) {
			if ($this->isTgPm()) {
				$this->isAdmin = true;
			} else {
				$getChatMember = new Telegram\Methods\GetChatMember();
				$getChatMember->user_id = $this->getTgFromId();
				$getChatMember->chat_id = $this->getTgChatId();
				$chatMember = $this->run($getChatMember);
				if ($chatMember instanceof Telegram\Types\ChatMember === false) {
					throw new \LogicException(sprintf('Unexpected type "%s" returned from getChatMember(), chat_id = "%s", user_id = "%s"',
							get_class($chatMember), $this->getTgChatId(), $this->getTgFromId())
					);
				}
				$this->isAdmin = TelegramHelper::isAdmin($chatMember);
			}
		}
		return $this->isAdmin;
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
}
