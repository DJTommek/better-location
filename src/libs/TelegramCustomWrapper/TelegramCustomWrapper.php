<?php declare(strict_types=1);

namespace TelegramCustomWrapper;

use \TelegramCustomWrapper\Events\Command\DebugCommand;
use \TelegramCustomWrapper\Events\Command\FavouritesCommand;
use \TelegramCustomWrapper\Events\Command\FeedbackCommand;
use \TelegramCustomWrapper\Events\Command\HelpCommand;
use \TelegramCustomWrapper\Events\Special\LocationEvent;
use \TelegramCustomWrapper\Events\Special\MessageEvent;
use \TelegramCustomWrapper\Events\Command\SettingsCommand;
use TelegramCustomWrapper\Events\Command\StartCommand;
use \TelegramCustomWrapper\Events\Command\UnknownCommand;
use \TelegramCustomWrapper\Events\Button\HelpButton;
use \TelegramCustomWrapper\Events\Button\FavouritesButton;
use \TelegramCustomWrapper\Events\Special\AddedToChatEvent;
use \TelegramCustomWrapper\Events\Special\FileEvent;
use TelegramCustomWrapper\Events\Special\InlineQueryEvent;
use \TelegramCustomWrapper\Events\Special\PhotoEvent;
use \unreal4u\TelegramAPI\Telegram;
use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\HttpClientRequestHandler;

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
		if ($update->edited_channel_post || $update->edited_message) {
			return 'Edit\'s are ignored';
		}
		if (TelegramHelper::addedToChat($update, \Config::TELEGRAM_BOT_NAME)) {
			return new AddedToChatEvent($update);
		}
		if (TelegramHelper::isViaBot($update, \Config::TELEGRAM_BOT_NAME)) {
			return 'I will ignore my own via_bot (from inline) messages.';
		}
		if (TelegramHelper::isChosenInlineQuery($update)) {
			// @TODO implement ChosenInlineQuery handler
			return 'ChosenInlineQuery handler is not implemented';
		}
		if (TelegramHelper::isInlineQuery($update)) {
			return new InlineQueryEvent($update);
		}

		$command = TelegramHelper::getCommand($update, \Config::TELEGRAM_COMMAND_STRICT);
		/** @noinspection PhpUnusedLocalVariableInspection */
		$params = TelegramHelper::getParams($update);

		if (TelegramHelper::isButtonClick($update)) {
			$update->callback_query->from->username = $update->callback_query->from->username === '' ? null : $update->callback_query->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->callback_query->from->displayname = TelegramHelper::getDisplayName($update->callback_query->from);

			switch ($command ? mb_strtolower($command) : null) {
				case HelpButton::CMD:
					return new HelpButton($update);
				case FavouritesButton::CMD:
					return new FavouritesButton($update);
				// @TODO log error, this should not happen. Edit: can happen if some command is no longer used (for example /stats was changed to /donor)
				default: // unknown
					return;
			}
		} else {

			$update->message->from->username = $update->message->from->username === '' ? null : $update->message->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->message->from->displayname = TelegramHelper::getDisplayName($update->message->from);
			if (TelegramHelper::isLocation($update)) {
				return new LocationEvent($update);
			} elseif (TelegramHelper::hasDocument($update)) {
				return new FileEvent($update);
			} elseif (TelegramHelper::hasPhoto($update)) {
				return new PhotoEvent($update);
			} else {
				switch ($command ? mb_strtolower($command) : null) {
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
