<?php

declare(strict_types=1);

namespace TelegramCustomWrapper\Events;

use BetterLocation\Service\WazeService;
use React\EventLoop\Factory;
use TelegramCustomWrapper\Events\Button\FavouritesButton;
use TelegramCustomWrapper\Events\Command\FavouritesCommand;
use TelegramCustomWrapper\Events\Command\FeedbackCommand;
use TelegramCustomWrapper\Events\Command\HelpCommand;
use TelegramCustomWrapper\Events\Command\StartCommand;
use TelegramCustomWrapper\SendMessage;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Markup;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\TgLog;
use Utils\DummyLogger;

abstract class Events
{
	protected $update;
	protected $tgLog;
	protected $loop;
	protected $user;
	protected $chat;

	protected $command = null;
	protected $params = [];

	public function __construct(Update $update) {
		$this->update = $update;

		$this->loop = Factory::create();
		$this->tgLog = new TgLog(\Config::TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($this->loop));

		if (TelegramHelper::isInlineQuery($update)) {
			$this->user = new \User($update->inline_query->from->id, $update->inline_query->from->username);
			if (empty($this->update->inline_query->location) === false) {
				try {
					$this->user->setLastKnownLocation(
						$this->update->inline_query->location->latitude,
						$this->update->inline_query->location->longitude,
					);
				} catch (\Exception $exception) {
					Debugger::log($exception, ILogger::EXCEPTION);
				}
			}
		} else if (TelegramHelper::isButtonClick($update)) {
			$this->user = new \User($update->callback_query->from->id, $update->callback_query->from->username);
			if ($update->callback_query->message) { // if clicked on button in message shared from inline (in "via @BotName"), there is no message
				/** @noinspection PhpUndefinedFieldInspection */
				$this->chat = new \Chat(
					$update->callback_query->message->chat->id,
					$update->callback_query->message->chat->type,
					empty($update->callback_query->message->chat->title) ? $update->callback_query->from->displayname : $update->callback_query->message->chat->title,
				);
			}
		} else {
			$this->user = new \User($update->message->from->id, $update->message->from->username);
			/** @noinspection PhpUndefinedFieldInspection */
			$this->chat = new \Chat(
				$update->message->chat->id,
				$update->message->chat->type,
				empty($update->message->chat->title) ? $update->message->from->displayname : $update->message->chat->title
			);
		}

		if (TelegramHelper::isInlineQuery($update) === false) {
			$this->command = TelegramHelper::getCommand($update);
			$this->params = TelegramHelper::getParams($update);
		}
	}

	abstract protected function getChatId();

	abstract protected function getMessageId();

	public function getFromId() {
		return $this->update->message->from->id;
	}

	public function getText() {
		return $this->update->message->text;
	}

	public function isPm() {
		return TelegramHelper::isPM($this->update);
	}

	public function isForward() {
		return TelegramHelper::isForward($this->update);
	}

	/**
	 * @param string $action
	 * @throws \Exception
	 * @noinspection PhpUnused
	 * @TODO Check if action string is valid
	 */
	public function sendAction(string $action = TelegramHelper::CHAT_ACTION_TYPING) {
		$chatAction = new SendChatAction();
		$chatAction->chat_id = $this->getChatId();
		$chatAction->action = $action;
		$this->run($chatAction);
	}

	/**
	 * Send message as reply to recieved message
	 *
	 * @param string $text
	 * @param array $options
	 * @return null
	 * @throws \Exception
	 */
	public function reply(string $text, array $options = []) {
		$msg = new SendMessage($this->getChatId(), $text, $this->getMessageId());
		if (isset($options['reply_markup'])) {
			$msg->setReplyMarkup($options['reply_markup']);
		}
		if (isset($options['disable_web_page_preview'])) {
			$msg->disableWebPagePreview($options['disable_web_page_preview']);
		}
		return $this->run($msg->msg);
	}

	/**
	 * @param $objectToSend
	 * @return mixed
	 * @throws \Exception
	 */
	public function run($objectToSend) {

		$promise = $this->tgLog->performApiRequest($objectToSend);
		$this->loop->run();
		DummyLogger::log(DummyLogger::NAME_TELEGRAM_OUTPUT, $objectToSend);
		$resultResponse = null;
		$resultException = null;
		$promise->then(
			function ($response) use (&$resultResponse) {
				$resultResponse = $response;
				DummyLogger::log(DummyLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $resultResponse);
			},
			function (\Exception $exception) use (&$resultException) {
				DummyLogger::log(DummyLogger::NAME_TELEGRAM_OUTPUT_RESPONSE, $exception->getMessage());
				$ignoreErorrs = [
					TelegramHelper::NOT_CHANGED,
					TelegramHelper::TOO_OLD,
				];
				if (in_array($exception->getMessage(), $ignoreErorrs) === false) {
					$resultException = $exception;
					Debugger::log(sprintf('TG API Command request error: "%s"', $exception->getMessage()), ILogger::EXCEPTION);
					Debugger::log($exception, ILogger::EXCEPTION);
				}
			}
		);

		if ($resultException) {
			throw $resultException;
		} else {
			return $resultResponse;
		}
	}

	/**
	 * @param bool $inline
	 * @throws \Exception
	 */
	protected function processHelp(bool $inline = false) {

		$lat = 50.087451;
		$lon = 14.420671;
		$wazeLink = WazeService::getLink($lat, $lon);
		$betterLocationWaze = WazeService::parseCoords($wazeLink);

		$text = sprintf('%s Welcome to @%s!', \Icons::LOCATION, \Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m a simple but smart bot to catch all possible location formats in any chats you invite me to, and generate links to your favourite location services such as Google maps, Waze, OpenStreetMaps etc.') . PHP_EOL;
		$text .= sprintf('For example, if you send a message containing the coordinates "<code>%1$f,%2$f</code>" or the link "https://www.waze.com/ul?ll=%1$f,%2$f" I will respond with this:', $lat, $lon) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $betterLocationWaze->generateBetterLocation();
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Formats I can read:</b>', \Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: WGS84 (decimal, degrees and even seconds) etc.') . PHP_EOL;
		$text .= sprintf('- special codes: <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a> etc.', 'https://what3words.com/', 'https://plus.codes/') . PHP_EOL;
		$text .= sprintf('- URL links: google.com, mapy.cz, intel.ingress.com etc.') . PHP_EOL;
		$text .= sprintf('- short URL links: goo.gl, mapy.cz, <a href="%s">Waze</a> etc.', 'https://www.waze.com/') . PHP_EOL;
		$text .= sprintf('- Telegram location') . PHP_EOL;
		$text .= sprintf('- EXIF from <b>uncompressed</b> images') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s <b>Inline:</b>', \Icons::INLINE) . PHP_EOL;
		$text .= sprintf('To send my Better locations to a group I am not in, or to a private message, just type <code>@%s</code>', \Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('- add any link, text, special code etc and choose one of the output') . PHP_EOL;
		$text .= sprintf('- send your current position (on mobile devices only)') . PHP_EOL;
		$text .= sprintf('- search literally anything via Google search API') . PHP_EOL;
		$text .= sprintf('%s <a href="%s">See video here</a>', \Icons::VIDEO, 'https://t.me/BetterLocationInfo/8') . PHP_EOL;
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
		$text .= sprintf('%s <b>Commands:</b>', \Icons::COMMAND) . PHP_EOL;
		$text .= sprintf('%s@%s - %s Learn more about me (this text)', HelpCommand::CMD, \Config::TELEGRAM_BOT_NAME, \Icons::INFO) . PHP_EOL;
		$text .= sprintf('%s@%s - %s Report invalid location or just contact the author', FeedbackCommand::CMD, \Config::TELEGRAM_BOT_NAME, \Icons::FEEDBACK) . PHP_EOL;
		$text .= sprintf('%s@%s %s - Manage your saved favourite locations (works only in PM)', FavouritesCommand::CMD, \Config::TELEGRAM_BOT_NAME, \Icons::FAVOURITE) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('%s For more info check out the <a href="%s">@BetterLocationInfo</a> channel.', \Icons::INFO, 'https://t.me/BetterLocationInfo/3') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;

		$messageSettings = [
			'disable_web_page_preview' => true,
			'reply_markup' => $this->getHelpButtons(),
		];

		if ($inline) {
			$this->replyButton($text, $messageSettings);
			$this->flash(sprintf('%s Help was refreshed.', \Icons::REFRESH));
		} else {
			$this->reply($text, $messageSettings);
		}
	}

	private function getHelpButtons(): Markup {
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [];

		$replyMarkupRow = [];

		$button = new Button();
		$button->text = sprintf('%s Help', \Icons::REFRESH);
		$button->callback_data = HelpCommand::CMD;
		$replyMarkupRow[] = $button;

		if ($this->isPm()) {
			$button = new Button();
			$button->text = sprintf('%s Favourites', \Icons::FAVOURITE);
			$button->callback_data = sprintf('%s %s', FavouritesCommand::CMD, FavouritesButton::ACTION_REFRESH);
			$replyMarkupRow[] = $button;
		}
		$replyMarkup->inline_keyboard[] = $replyMarkupRow;
		return $replyMarkup;
	}

	protected function processFavouritesList(bool $inline = false) {
		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				[ // button
					'text' => sprintf('%s Help', \Icons::BACK),
					'callback_data' => HelpCommand::CMD,
				],
				[ // button
					'text' => sprintf('%s Refresh list', \Icons::REFRESH),
					'callback_data' => sprintf('%s %s', FavouritesCommand::CMD, FavouritesButton::ACTION_REFRESH),
				],
			],
		];

		$text = sprintf('%s A list of <b>favourite</b> locations saved by @%s.', \Icons::FAVOURITE, \Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('Here you can manage your favourite locations, which will appear as soon as you type @%s in any chat.', \Config::TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I don\'t have to be in that chat in order for it to work!') . PHP_EOL;
		$text .= PHP_EOL;
		if (count($this->user->getFavourites()) === 0) {
			$text .= sprintf('%s Sadly, you don\'t have any favourite locations saved yet.', \Icons::INFO) . PHP_EOL;
		} else {
			$text .= sprintf('%s You have %d favourite location(s):', \Icons::INFO, count($this->user->getFavourites())) . PHP_EOL;
			foreach ($this->user->getFavourites() as $favourite) {
				$text .= $favourite->generateBetterLocation();

				$shareFavouriteButton = new Button();
				$shareFavouriteButton->text = sprintf('Share %s', $favourite->getPrefixMessage());
				$shareFavouriteButton->switch_inline_query = $favourite->__toString();

				$replyMarkup->inline_keyboard[] = [$shareFavouriteButton];
				$buttonRow = [];

				$renameFavouriteButton = new Button();
				$renameFavouriteButton->text = sprintf('%s Rename', \Icons::CHANGE);
				$renameFavouriteButton->switch_inline_query_current_chat = sprintf('%s %s %F %F %s',
					StartCommand::FAVOURITE,
					StartCommand::FAVOURITE_RENAME,
					$favourite->getLat(),
					$favourite->getLon(),
					mb_substr($favourite->getPrefixMessage(), 2), // Remove favourites icon and space (@TODO should not use getPrefixMessage())
				);
				$buttonRow[] = $renameFavouriteButton;

				$deleteFavouriteButton = new Button();
				$deleteFavouriteButton->text = sprintf('%s Delete', \Icons::DELETE);
				$deleteFavouriteButton->callback_data = sprintf('%s %s %F %F', FavouritesCommand::CMD, FavouritesButton::ACTION_DELETE, $favourite->getLat(), $favourite->getLon());
				$buttonRow[] = $deleteFavouriteButton;

				$replyMarkup->inline_keyboard[] = $buttonRow;
			}
		}
		$text .= sprintf('%s To add a location to your favourites, just send any link, coordinates etc. to me via PM and click on the %s button in my response.', \Icons::INFO, \Icons::FAVOURITE) . PHP_EOL;

		$messageSettings = [
			'disable_web_page_preview' => true,
			'reply_markup' => $replyMarkup,
		];

		if ($inline) {
			$this->replyButton($text, $messageSettings);
			$this->flash(sprintf('%s List of favourite locations was refreshed.', \Icons::REFRESH));
		} else {
			$this->reply($text, $messageSettings);
		}


	}
}
