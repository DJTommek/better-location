<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\WazeService;
use App\Chat;
use App\Config;
use App\Icons;
use App\Pluginer\Pluginer;
use App\TelegramCustomWrapper\BetterLocationMessageSettings;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Button\SettingsButton;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\ProcessedMessageResult;
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
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
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

	public static function getTgCmd(bool $withSuffix = false): string
	{
		if ($withSuffix) {
			return sprintf('%s@%s', static::CMD, Config::TELEGRAM_BOT_NAME);
		} else {
			return static::CMD;
		}
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

	/** @throws \Exception */
	protected function processHelp(bool $inline = false)
	{
		$lat = 50.087451;
		$lon = 14.420671;
		$wazeLink = WazeService::getShareLink($lat, $lon);
		$betterLocationWaze = WazeService::processStatic($wazeLink)->getFirst();

		$text = sprintf('%s Welcome to @%s!', Icons::LOCATION, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m a simple but smart bot to catch all possible location formats in any chats you invite me to, and generate links to your favourite location services such as Google maps, Waze, OpenStreetMap etc.') . PHP_EOL;
		$text .= sprintf('For example, if you send a message containing the coordinates "<code>%f,%f</code>" or the link "%s" I will respond with this:', $lat, $lon, $wazeLink) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $betterLocationWaze->generateMessage($this->getMessageSettings());
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Formats I can read:</b>', Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: <a href="%s">WGS84</a>, <a href="%s">USNG</a>, <a href="%s">MGRS</a>, <a href="%s">UTM</a>, ...',
				'https://en.wikipedia.org/wiki/World_Geodetic_System',
				'https://en.wikipedia.org/wiki/United_States_National_Grid',
				'https://en.wikipedia.org/wiki/Military_Grid_Reference_System',
				'https://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system',
			) . PHP_EOL;
		$text .= sprintf('- codes: <a href="%s">Geocaching GCxxx</a>, <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a>, ...',
				'https://geocaching.com/',
				'https://what3words.com/',
				'https://plus.codes/',
			) . PHP_EOL;
		$text .= sprintf('- links: google.com, glympse.com, mapy.cz, intel.ingress.com, ...') . PHP_EOL;
		$text .= sprintf('- short links: goo.gl, bit.ly, tinyurl.com, t.co, tiny.cc, ...') . PHP_EOL;
		$text .= sprintf('- Telegram (live) location and venues') . PHP_EOL;
		$text .= sprintf('- <a href="%s">Exif</a> from <b>uncompressed</b> images', 'https://wikipedia.org/wiki/Exif') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Inline:</b>', Icons::INLINE) . PHP_EOL;
		$text .= sprintf('To send my Better locations to a group I am not in, or to a private chat, just type <code>@%s</code>', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('- add any link, text, special code etc and choose one of the output') . PHP_EOL;
		$text .= sprintf('- send your current position (on mobile devices only)') . PHP_EOL;
		$text .= sprintf('- send previously saved favourited locations') . PHP_EOL;
		$text .= sprintf('- search literally anything via Google search API') . PHP_EOL;
		$text .= sprintf('%s <a href="https://t.me/BetterLocation/8">See video here</a>', Icons::VIDEO) . PHP_EOL;
		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Private chat:</b>', Icons::USER) . PHP_EOL;
//		$text .= sprintf('Just send me some link to or coordinate and I will generate message <b>just</b> for you.') . PHP_EOL;
//		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Group chat:</b>', Icons::GROUP) . PHP_EOL;
//		$text .= sprintf('Almost everything is the same as in private message except:') . PHP_EOL;
//		$text .= sprintf('I will be quiet unless someone use command which I know or send some location') . PHP_EOL;
//		$text .= PHP_EOL;
//		$text .= sprintf('%s <b>Channel:</b>', Icons::CHANNEL) . PHP_EOL;
//		$text .= sprintf('Currently not supported. Don\'t hesitate to ping author if you are interested in this feature.') . PHP_EOL;
//		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Commands:</b>', Icons::COMMAND) . PHP_EOL;
		$text .= sprintf('%s - %s Learn more about me (this text)', HelpCommand::getTgCmd(!$this->isTgPm()), Icons::INFO) . PHP_EOL;
		$text .= sprintf('%s - %s Report invalid location or just contact the author', FeedbackCommand::getTgCmd(!$this->isTgPm()), Icons::FEEDBACK) . PHP_EOL;
		$text .= sprintf('%s - %s Manage your saved favourite locations (works only in PM)', FavouritesCommand::getTgCmd(!$this->isTgPm()), Icons::FAVOURITE) . PHP_EOL;
		$text .= sprintf('%s - %s Adjust your settings (works only in PM)', SettingsCommand::getTgCmd(!$this->isTgPm()), Icons::SETTINGS) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s For more info check out the <a href="%s">@BetterLocation</a> channel.', Icons::INFO, 'https://t.me/BetterLocation/3') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;

		$markup = $this->getHelpButtons();

		if ($inline) {
			$this->replyButton($text, $markup, [
				'disable_web_page_preview' => true,
			]);
			$this->flash(sprintf('%s Help was refreshed.', Icons::REFRESH));
		} else {
			$this->reply($text, $markup, [
				'disable_web_page_preview' => true,
			]);
		}
	}

	private function getHelpButtons(): Markup
	{
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // first row
				new Button([
					'text' => sprintf('%s Help', Icons::REFRESH),
					'callback_data' => HelpButton::CMD,
				]),
			], [ // second row
				new Button([
					'text' => sprintf('%s Try inline searching', Icons::INLINE),
					'switch_inline_query_current_chat' => 'Czechia Prague',
				]),
			],
		];

		if ($this->isTgPm() === true) {
			// add buton into first row
			$replyMarkup->inline_keyboard[0][] = new Button([
				'text' => sprintf('%s Favourites', Icons::FAVOURITE),
				'callback_data' => sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH),
			]);
		}
		return $replyMarkup;
	}

	protected function processFavouritesList(bool $inline = false)
	{
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				new Button([ // button
					'text' => sprintf('%s Help', Icons::BACK),
					'callback_data' => HelpButton::CMD,
				]),
				new Button([ // button
					'text' => sprintf('%s Refresh list', Icons::REFRESH),
					'callback_data' => sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH),
				]),
			],
		];

		$text = sprintf('%s A list of <b>favourite</b> locations saved by @%s.', Icons::FAVOURITE, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Here you can manage your favourite locations, which will appear as soon as you type @%s in any chat.', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I don\'t have to be in that chat in order for it to work!') . PHP_EOL;
		$text .= PHP_EOL;
		if (count($this->user->getFavourites()) === 0) {
			$text .= sprintf('%s Sadly, you don\'t have any favourite locations saved yet.', Icons::INFO) . PHP_EOL;
		} else {
			$staticMapUrl = $this->user->getFavourites()->getStaticMapUrl();
			if ($staticMapUrl === null) {
				$text = Icons::INFO;
			} else {
				$text .= sprintf('<a href="%s">%s</a>', (string)$staticMapUrl, Icons::INFO);
			}
			$text .= sprintf(' You have %d favourite location(s):', count($this->user->getFavourites())) . PHP_EOL;
			foreach ($this->user->getFavourites() as $favourite) {
				$text .= $favourite->generateMessage($this->getMessageSettings());

				$shareFavouriteButton = new Button();
				$shareFavouriteButton->text = sprintf('Share %s', $favourite->getPrefixMessage());
				$shareFavouriteButton->switch_inline_query = $favourite->__toString();

				$replyMarkup->inline_keyboard[] = [$shareFavouriteButton];
				$buttonRow = [];

				$renameFavouriteButton = new Button();
				$renameFavouriteButton->text = sprintf('%s Rename', Icons::CHANGE);
				$renameFavouriteButton->switch_inline_query_current_chat = sprintf('%s %s %F %F %s',
					StartCommand::FAVOURITE,
					StartCommand::FAVOURITE_RENAME,
					$favourite->getLat(),
					$favourite->getLon(),
					mb_substr($favourite->getPrefixMessage(), 2), // Remove favourites icon and space (@TODO should not use getPrefixMessage())
				);
				$buttonRow[] = $renameFavouriteButton;

				$deleteFavouriteButton = new Button();
				$deleteFavouriteButton->text = sprintf('%s Delete', Icons::DELETE);
				$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesButton::CMD, FavouritesButton::ACTION_DELETE, $favourite->getLat(), $favourite->getLon());
				$buttonRow[] = $deleteFavouriteButton;

				$replyMarkup->inline_keyboard[] = $buttonRow;
			}
		}
		$text .= sprintf('%s To add a location to your favourites, just send any link, coordinates etc. to me via PM and click on the %s button in my response.', Icons::INFO, Icons::FAVOURITE) . PHP_EOL;

		if ($inline) {
			$this->replyButton($text, $replyMarkup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
			$this->flash(sprintf('%s List of favourite locations was refreshed.', Icons::REFRESH));
		} else {
			$this->reply($text, $replyMarkup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		}
	}

	protected function processSettings(bool $inline = false): void
	{
		$collection = WazeService::processStatic(WazeService::getShareLink(50.087451, 14.420671))->getCollection();
		$processedCollection = new ProcessedMessageResult($collection, $this->getMessageSettings(), $this->getPluginer());
		$processedCollection->process();

		$text = sprintf('%s <b>Chat settings</b> for @%s. ', Icons::SETTINGS, Config::TELEGRAM_BOT_NAME);
		if ($this->isTgPm()) {
			$text .= PHP_EOL . sprintf('%s This private chat settings will be used while sending messages via inline mode, overriding chat settings.', Icons::INFO) . PHP_EOL . PHP_EOL;
		}
		$text .= 'Example message:' . PHP_EOL;
		$text .= $processedCollection->getText();
		$replyMarkup = $processedCollection->getMarkup(1);

		$previewButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->chat->settingsPreview()) {
			$previewButton->text = sprintf('%s Map preview', Icons::ENABLED);
			$previewButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_PREVIEW);
		} else {
			$previewButton->text = sprintf('%s Map preview', Icons::DISABLED);
			$previewButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_PREVIEW);
		}
		$buttonRow[] = $previewButton;

		$showAddressButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->chat->settingsShowAddress()) {
			$showAddressButton->text = sprintf('%s Address', Icons::ENABLED);
			$showAddressButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SHOW_ADDRESS);
		} else {
			$showAddressButton->text = sprintf('%s Address', Icons::DISABLED);
			$showAddressButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SHOW_ADDRESS);
		}
		$buttonRow[] = $showAddressButton;

		$sendNativeLocationButton = new \unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button();
		if ($this->chat->getSendNativeLocation()) {
			$sendNativeLocationButton->text = sprintf('%s Native location', Icons::ENABLED);
			$sendNativeLocationButton->callback_data = sprintf('%s %s false', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SEND_NATIVE_LOCATION);
		} else {
			$sendNativeLocationButton->text = sprintf('%s Native location', Icons::DISABLED);
			$sendNativeLocationButton->callback_data = sprintf('%s %s true', SettingsButton::CMD, SettingsButton::ACTION_SETTINGS_SEND_NATIVE_LOCATION);
		}
		$buttonRow[] = $sendNativeLocationButton;

		$replyMarkup->inline_keyboard[] = $buttonRow;
		$chatSettingsUrl = Config::getAppUrl('/chat/' . $this->getTgChatId());
		$replyMarkup->inline_keyboard[] = [
			TelegramHelper::loginUrlButton('More settings', $chatSettingsUrl)
		];

		if ($inline) {
			$this->replyButton($text, $replyMarkup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		} else {
			$this->reply($text, $replyMarkup, ['disable_web_page_preview' => !$this->chat->settingsPreview()]);
		}
	}

	protected function processLogin()
	{
		if ($this->isTgPm()) {
			$appUrl = Config::getAppUrl();
			$text = sprintf('%s <b>Login</b> for <a href="%s">%s</a>.', Icons::LOGIN, $appUrl->getAbsoluteUrl(), $appUrl->getDomain(0)) . PHP_EOL;
			$text .= sprintf('Click on button below to login to access your settings, favourites, etc. on %s website', $appUrl->getDomain(0));

			$replyMarkup = new Markup();
			$replyMarkup->inline_keyboard[] = [
				TelegramHelper::loginUrlButton('Login in browser')
			];

			$this->reply($text, $replyMarkup);
		} else {
			$this->reply(sprintf('%s Command <code>%s</code> is available only in private message, open @%s.',
				Icons::ERROR, self::getTgCmd(), Config::TELEGRAM_BOT_NAME
			));
		}
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
