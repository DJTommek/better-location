<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper\Events;

use App\BetterLocation\Service\WazeService;
use App\Chat;
use App\Config;
use App\Icons;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\SendMessage;
use App\TelegramCustomWrapper\TelegramHelper;
use App\User;
use App\Utils\SimpleLogger;
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
	protected $update;
	protected $tgLog;
	protected $loop;
	protected $user;
	protected $chat;

	protected $command = null;
	protected $params = [];

	abstract public function handleWebhookUpdate();

	public function __construct(Update $update)
	{
		$this->update = $update;

		$this->loop = Factory::create();
		$this->tgLog = new TgLog(Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($this->loop));
		$this->user = new User($this->getFromId(), $this->getFromDisplayname());
		if ($this->hasMessage()) {
			$this->chat = new Chat(
				$this->getChatId(),
				$this->getChat()->type,
				$this->getChatDisplayname()
			);
		}

		if (TelegramHelper::isInlineQuery($update) === false && TelegramHelper::isEdit($update) === false) {
			$this->command = TelegramHelper::getCommand($update);
			$this->params = TelegramHelper::getParams($update);
		}
	}

	public function getChat(): Telegram\Types\Chat
	{
		return $this->getMessage()->chat;
	}

	public function getFrom(): Telegram\Types\User
	{
		return $this->getMessage()->from;
	}

	public function getChatId(): int
	{
		return $this->getChat()->id;
	}

	public function getFromId(): int
	{
		return $this->getFrom()->id;
	}

	public function getFromDisplayname(): string
	{
		return TelegramHelper::getUserDisplayname($this->getFrom());
	}

	public function getChatDisplayname(): string
	{
		return TelegramHelper::getChatDisplayname($this->getChat());
	}

	public function getMessageId(): int
	{
		return $this->getMessage()->message_id;
	}

	public function getText(): string
	{
		return $this->getMessage()->text;
	}

	abstract public function getMessage(): Telegram\Types\Message;

	/** @return bool overridden with false, where Telegram\Types\Message is not available */
	public function hasMessage(): bool
	{
		return true;
	}

	public static function getCmd(bool $withSuffix = false): string
	{
		if ($withSuffix) {
			return sprintf('%s@%s', static::CMD, Config::TELEGRAM_BOT_NAME);
		} else {
			return static::CMD;
		}
	}

	/** @return bool|null null if unknown (eg. clicked on button in via_bot message) */
	public function isPm(): ?bool
	{
		return TelegramHelper::isPM($this->update);
	}

	public function isForward()
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
		$chatAction->chat_id = $this->getChatId();
		$chatAction->action = $action;
		$this->run($chatAction);
	}

	/** Send message as reply to recieved message */
	public function reply(string $text, array $options = []): Telegram\Types\Message
	{
		$msg = new SendMessage($this->getChatId(), $text, $this->getMessageId());
		if (isset($options['reply_markup'])) {
			$msg->setReplyMarkup($options['reply_markup']);
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disableWebPagePreview($options['disable_web_page_preview']);
		}
		/** @var Telegram\Types\Message $response It always should be this type. Other should throw exception */
		$response = $this->run($msg->msg);
		return $response;
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
		$wazeLink = WazeService::getLink($lat, $lon);
		$betterLocationWaze = WazeService::parseCoords($wazeLink);

		$text = sprintf('%s Welcome to @%s!', Icons::LOCATION, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m a simple but smart bot to catch all possible location formats in any chats you invite me to, and generate links to your favourite location services such as Google maps, Waze, OpenStreetMaps etc.') . PHP_EOL;
		$text .= sprintf('For example, if you send a message containing the coordinates "<code>%f,%f</code>" or the link "%s" I will respond with this:', $lat, $lon, $wazeLink) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $betterLocationWaze->generateMessage();
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Formats I can read:</b>', Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: <a href="%s">WGS84</a>, <a href="%s">USNG</a>, <a href="%s">MGRS</a>, <a href="%s">UTM</a>, ...',
				'https://en.wikipedia.org/wiki/World_Geodetic_System',
				'https://en.wikipedia.org/wiki/United_States_National_Grid',
				'https://en.wikipedia.org/wiki/Military_Grid_Reference_System',
				'https://en.wikipedia.org/wiki/Universal_Transverse_Mercator_coordinate_system',
			) . PHP_EOL;
		$text .= sprintf('- codes: <a href="%s">Geocaching GCxxx</a>, <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a>, ...',
				'https://what3words.com/',
				'https://plus.codes/',
				'https://geocaching.com/',
			) . PHP_EOL;
		$text .= sprintf('- links: google.com, glympse.com, mapy.cz, intel.ingress.com, ...') . PHP_EOL;
		$text .= sprintf('- short links: goo.gl, bit.ly, tinyurl.com, t.co, tiny.cc, ...') . PHP_EOL;
		$text .= sprintf('- Telegram (live) location and venues') . PHP_EOL;
		$text .= sprintf('- EXIF from <b>uncompressed</b> images') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Inline:</b>', Icons::INLINE) . PHP_EOL;
		$text .= sprintf('To send my Better locations to a group I am not in, or to a private chat, just type <code>@%s</code>', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('- add any link, text, special code etc and choose one of the output') . PHP_EOL;
		$text .= sprintf('- send your current position (on mobile devices only)') . PHP_EOL;
		$text .= sprintf('- send previously saved favourited locations') . PHP_EOL;
		$text .= sprintf('- search literally anything via Google search API') . PHP_EOL;
		$text .= sprintf('%s <a href="%s">See video here</a>', Icons::VIDEO, 'https://t.me/BetterLocation/8') . PHP_EOL;
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
		$text .= sprintf('%s - %s Learn more about me (this text)', HelpCommand::getCmd(!$this->isPm()), Icons::INFO) . PHP_EOL;
		$text .= sprintf('%s - %s Report invalid location or just contact the author', FeedbackCommand::getCmd(!$this->isPm()), Icons::FEEDBACK) . PHP_EOL;
		$text .= sprintf('%s - %s Manage your saved favourite locations (works only in PM)', FavouritesCommand::getCmd(!$this->isPm()), Icons::FAVOURITE) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s For more info check out the <a href="%s">@BetterLocation</a> channel.', Icons::INFO, 'https://t.me/BetterLocation/3') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;

		$messageSettings = [
			'disable_web_page_preview' => true,
			'reply_markup' => $this->getHelpButtons(),
		];

		if ($inline) {
			$this->replyButton($text, $messageSettings);
			$this->flash(sprintf('%s Help was refreshed.', Icons::REFRESH));
		} else {
			$this->reply($text, $messageSettings);
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

		if ($this->isPm() === true) {
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
				[ // button
					'text' => sprintf('%s Help', Icons::BACK),
					'callback_data' => HelpButton::CMD,
				],
				[ // button
					'text' => sprintf('%s Refresh list', Icons::REFRESH),
					'callback_data' => sprintf('%s %s', FavouritesButton::CMD, FavouritesButton::ACTION_REFRESH),
				],
			],
		];

		$text = sprintf('%s A list of <b>favourite</b> locations saved by @%s.', Icons::FAVOURITE, Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Here you can manage your favourite locations, which will appear as soon as you type @%s in any chat.', Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I don\'t have to be in that chat in order for it to work!') . PHP_EOL;
		$text .= PHP_EOL;
		if (count($this->user->getFavourites()) === 0) {
			$text .= sprintf('%s Sadly, you don\'t have any favourite locations saved yet.', Icons::INFO) . PHP_EOL;
		} else {
			$text .= sprintf('%s You have %d favourite location(s):', Icons::INFO, count($this->user->getFavourites())) . PHP_EOL;
			foreach ($this->user->getFavourites() as $favourite) {
				$text .= $favourite->generateMessage();

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

		$messageSettings = [
			'disable_web_page_preview' => true,
			'reply_markup' => $replyMarkup,
		];

		if ($inline) {
			$this->replyButton($text, $messageSettings);
			$this->flash(sprintf('%s List of favourite locations was refreshed.', Icons::REFRESH));
		} else {
			$this->reply($text, $messageSettings);
		}
	}
}
