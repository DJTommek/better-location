<?php

declare(strict_types=1);

namespace TelegramCustomWrapper\Events;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Coordinates\WG84DegreesService;
use BetterLocation\Service\WazeService;
use React\EventLoop\Factory;
use TelegramCustomWrapper\SendMessage;
use TelegramCustomWrapper\TelegramHelper;
use Tracy\Debugger;
use Tracy\ILogger;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram\Methods\SendChatAction;
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
		$this->tgLog = new TgLog(TELEGRAM_BOT_TOKEN, new HttpClientRequestHandler($this->loop));

		if (TelegramHelper::isButtonClick($update)) {
			$this->user = new \User($update->callback_query->from->id, $update->callback_query->from->username);
			/** @noinspection PhpUndefinedFieldInspection */
			$this->chat = new \Chat(
				$update->callback_query->message->chat->id,
				$update->callback_query->message->chat->type,
				empty($update->callback_query->message->chat->title) ? $update->callback_query->from->displayname : $update->callback_query->message->chat->title,
			);
		} else {
			$this->user = new \User($update->message->from->id, $update->message->from->username);
			/** @noinspection PhpUndefinedFieldInspection */
			$this->chat = new \Chat(
				$update->message->chat->id,
				$update->message->chat->type,
				empty($update->message->chat->title) ? $update->message->from->displayname : $update->message->chat->title
			);
		}

		$this->command = TelegramHelper::getCommand($update);
		$this->params = TelegramHelper::getParams($update);
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
	 * @return null
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
				$resultException = $exception;
				Debugger::log(sprintf('TG API Command request error: "%s"', $exception->getMessage()), ILogger::EXCEPTION);
				Debugger::log($exception, ILogger::EXCEPTION);
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
	 * @throws \BetterLocation\Service\Exceptions\InvalidLocationException
	 * @throws \Exception
	 */
	protected function processHelp(bool $inline = false) {

		$lat = 50.0877258;
		$lon = 14.4211267;
		$wazeLink = WazeService::getLink($lat, $lon);
		$betterLocationWaze = WazeService::parseCoords($wazeLink);
		$betterLocationCoords = new BetterLocation($lat, $lon, WG84DegreesService::NAME);

		$text = sprintf('%s Welcome to @%s!', \Icons::LOCATION, TELEGRAM_BOT_NAME) . PHP_EOL;
		$text .= sprintf('I\'m simple but smart bot to catch all possible location formats in any chats you invite me in and generate links to your favourite location services as Google maps, Waze, OpenStreetMaps etc.') . PHP_EOL;
		$text .= sprintf('Example, if you send coordinates "<code>%1$f,%2$f</code>" or link "https://www.waze.com/ul?ll=%1$f,%2$f" I will respond with this:', $lat, $lon) . PHP_EOL;
		$text .= PHP_EOL;
		$text .= $betterLocationCoords->generateBetterLocation();
		$text .= $betterLocationWaze->generateBetterLocation();
		// @TODO newline is filled in $result (yeah, it shouldn't be like that..)
		$text .= sprintf('%s <b>Features:</b>', \Icons::FEATURES) . PHP_EOL;
		$text .= sprintf('- coordinates: WGS84 (decimal, degrees and even seconds) etc.') . PHP_EOL;
		$text .= sprintf('- special codes: <a href="%s">What3Words</a>, <a href="%s">Open Location Codes</a> etc.', 'https://what3words.com/', 'https://plus.codes/') . PHP_EOL;
		$text .= sprintf('- URL links: google.com, mapy.cz, intel.ingress.com etc.') . PHP_EOL;
		$text .= sprintf('- short URL links: goo.gl, mapy.cz, <a href="%s">Waze</a> etc.', 'https://www.waze.com/') . PHP_EOL;
		$text .= sprintf('- Telegram location') . PHP_EOL;
		$text .= sprintf('- EXIF from <b>uncompressed</b> images') . PHP_EOL;
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
		$text .= sprintf('/help - Find way to gain more knowledge (this text).') . PHP_EOL;
//		$text .= sprintf('/debug - get your and chat ID') . PHP_EOL;
//		$text .= sprintf('/settings - adjust behaviour in this chat') . PHP_EOL;
		$text .= sprintf('/feedback - Report invalid location or just contact author.') . PHP_EOL;
		$text .= PHP_EOL;
		$text .= sprintf('Official Github: <a href="%1$s%2$s">%2$s</a>', 'https://github.com/', 'DJTommek/better-location') . PHP_EOL;
		$text .= sprintf('Author: <a href="%1$s%2$s">@%2$s</a>', 'https://t.me/', 'DJTommek') . PHP_EOL;
		$text .= PHP_EOL;

//		$text .= sprintf(Icons::WARNING . ' <b>Warning</b>: Bot is currently in active development so there is no guarantee that it will work at all times. Check Github for more info.') . PHP_EOL;

		$replyMarkup = new Markup();
		$replyMarkup->inline_keyboard = [
			[ // row of buttons
				[ // button
					'text' => sprintf('Help'),
					'callback_data' => sprintf('/help'),
				],
			],
		];

		$messageSettings = [
			'disable_web_page_preview' => true,
			'reply_markup' => $replyMarkup,
		];

		if ($inline) {
			$this->replyButton($text, $messageSettings);
		} else {
			$this->reply($text, $messageSettings);
		}
	}
}