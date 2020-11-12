<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\TelegramCustomWrapper\Events\Button\FavouritesButton;
use App\TelegramCustomWrapper\Events\Button\HelpButton;
use App\TelegramCustomWrapper\Events\Button\InvalidButton;
use App\TelegramCustomWrapper\Events\Command\DebugCommand;
use App\TelegramCustomWrapper\Events\Command\FavouritesCommand;
use App\TelegramCustomWrapper\Events\Command\FeedbackCommand;
use App\TelegramCustomWrapper\Events\Command\HelpCommand;
use App\TelegramCustomWrapper\Events\Command\SettingsCommand;
use App\TelegramCustomWrapper\Events\Command\StartCommand;
use App\TelegramCustomWrapper\Events\Command\UnknownCommand;
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

	public function __construct($botToken, $botName)
	{
		$this->botToken = $botToken;
		$this->botName = $botName;

		$this->loop = \React\EventLoop\Factory::create();
		$this->tgLog = new TgLog($botToken, new HttpClientRequestHandler($this->loop));
	}

	public function handleUpdate($updateData)
	{
		$update = new Telegram\Types\Update($updateData);
		if ($update->update_id === 0) { // default value
			throw new \Exception('Telegram webhook API data are missing!');
		}
		if (TelegramHelper::isEdit($update)) {
			return 'Edit\'s are ignored';
		}
		if (TelegramHelper::addedToChat($update, Config::TELEGRAM_BOT_NAME)) {
			return new AddedToChatEvent($update);
		}
		if (TelegramHelper::isViaBot($update, Config::TELEGRAM_BOT_NAME)) {
			return 'I will ignore my own via_bot (from inline) messages.';
		}
		if (TelegramHelper::isChosenInlineQuery($update)) {
			// @TODO implement ChosenInlineQuery handler
			return 'ChosenInlineQuery handler is not implemented';
		}
		if (TelegramHelper::isInlineQuery($update)) {
			return new InlineQueryEvent($update);
		}

		$command = TelegramHelper::getCommand($update, Config::TELEGRAM_COMMAND_STRICT);

		if (TelegramHelper::isButtonClick($update)) {
			switch ($command) {
				case HelpButton::CMD:
					return new HelpButton($update);
				case FavouritesButton::CMD:
					return new FavouritesButton($update);
				default: // unknown: malicious request or button command has changed
					return new InvalidButton($update);
			}
		} else {
			if (TelegramHelper::isLocation($update)) {
				return new LocationEvent($update);
			} elseif (TelegramHelper::hasDocument($update)) {
				return new FileEvent($update);
			} elseif (TelegramHelper::hasPhoto($update)) {
				return new PhotoEvent($update);
			} else {
				switch ($command) {
					case StartCommand::CMD:
						return new StartCommand($update);
					case HelpCommand::CMD:
						return new HelpCommand($update);
					case DebugCommand::CMD:
						return new DebugCommand($update);
					case SettingsCommand::CMD:
						return new SettingsCommand($update);
					case FavouritesCommand::CMD:
						return new FavouritesCommand($update);
					case FeedbackCommand::CMD:
						return new FeedbackCommand($update);
					case null: // message without command
						return new MessageEvent($update);
					default: // unknown command
						return new UnknownCommand($update);
				}
			}
		}
	}
}
