<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Button\InvalidButton;
use App\TelegramCustomWrapper\Events\Button\RefreshButton;
use App\TelegramCustomWrapper\Events\Button\SettingsButton;
use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\Events\Command\UnknownCommand;
use App\TelegramCustomWrapper\Events\Edit\LocationEdit;
use App\TelegramCustomWrapper\Events\Events;
use App\TelegramCustomWrapper\Events\Special\AddedToChatEvent;
use App\TelegramCustomWrapper\Events\Special\FileEvent;
use App\TelegramCustomWrapper\Events\Special\InlineQueryEvent;
use App\TelegramCustomWrapper\Events\Special\LocationEvent;
use App\TelegramCustomWrapper\Events\Special\MessageEvent;
use App\TelegramCustomWrapper\Events\Special\PhotoEvent;
use unreal4u\TelegramAPI\HttpClientRequestHandler;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\TgLog;

class TelegramCustomWrapper
{
	private $botToken;
	private $botName;

	private $tgLog;
	private $loop;

	/** @var Events */
	private $event;
	/** @var string */
	private $eventNote;

	public function __construct($botToken, $botName)
	{
		$this->botToken = $botToken;
		$this->botName = $botName;

		$this->loop = \React\EventLoop\Factory::create();
		$this->tgLog = new TgLog($botToken, new HttpClientRequestHandler($this->loop));
	}

	public function getUpdateEvent(Telegram\Types\Update $update)
	{
		if ($update->update_id === 0) { // default value
			throw new \Exception('Telegram webhook API data are missing!');
		} else if (isset($update->my_chat_member)) {
			$this->eventNote = '$update->my_chat_member is ignored';
		} else if (TelegramHelper::isEdit($update)) {
			if (TelegramHelper::isLocation($update)) {
				$this->event = new LocationEdit($update);
			} else {
				$this->eventNote = 'Edit\'s are ignored';
			}
		} else if (TelegramHelper::isChannel($update)) {
			$this->eventNote = 'Channel messages are ignored';
		} else if (TelegramHelper::addedToChat($update, Config::TELEGRAM_BOT_NAME)) {
			$this->event = new AddedToChatEvent($update);
		} else if (TelegramHelper::isViaBot($update, Config::TELEGRAM_BOT_NAME)) {
			$this->eventNote = 'I will ignore my own via_bot (from inline) messages.';
		} else if (TelegramHelper::isChosenInlineQuery($update)) {
			// @TODO implement ChosenInlineQuery handler
			$this->eventNote = 'ChosenInlineQuery handler is not implemented';
		} else if (TelegramHelper::isInlineQuery($update)) {
			$this->event = new InlineQueryEvent($update);
		} else {
			$command = TelegramHelper::getCommand($update, Config::TELEGRAM_COMMAND_STRICT);
			if (TelegramHelper::isButtonClick($update)) {
				switch ($command) {
					case HelpButton::CMD:
						$this->event = new HelpButton($update);
						break;
					case FavouritesButton::CMD:
						$this->event = new FavouritesButton($update);
						break;
					case RefreshButton::CMD:
						$this->event = new RefreshButton($update);
						break;
					case SettingsButton::CMD:
						$this->event = new SettingsButton($update);
						break;
					default: // unknown: malicious request or button command has changed
						$this->event = new InvalidButton($update);
						break;
				}
			} else {
				if (TelegramHelper::isLocation($update)) {
					$this->event = new LocationEvent($update);
				} elseif (TelegramHelper::hasDocument($update)) {
					$this->event = new FileEvent($update);
				} elseif (TelegramHelper::hasPhoto($update)) {
					$this->event = new PhotoEvent($update);
				} else {
					switch ($command) {
						case StartCommand::CMD:
							$this->event = new StartCommand($update);
							break;
						case HelpCommand::CMD:
							$this->event = new HelpCommand($update);
							break;
						case DebugCommand::CMD:
							$this->event = new DebugCommand($update);
							break;
						case SettingsCommand::CMD:
							$this->event = new SettingsCommand($update);
							break;
						case FavouritesCommand::CMD:
							$this->event = new FavouritesCommand($update);
							break;
						case FeedbackCommand::CMD:
							$this->event = new FeedbackCommand($update);
							break;
						case null: // message without command
							$this->event = new MessageEvent($update);
							break;
						default: // unknown command
							$this->event = new UnknownCommand($update);
							break;
					}
				}
			}
		}
	}

	public function getEvent(): ?Events
	{
		return $this->event;
	}

	public function getEventNote(): string
	{
		return $this->eventNote;
	}

	public function handle()
	{
		$this->event->handleWebhookUpdate();
	}
}
