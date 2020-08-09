<?php

declare(strict_types=1);

namespace TelegramCustomWrapper;

use \TelegramCustomWrapper\Events\Command\DebugCommand;
use \TelegramCustomWrapper\Events\Command\FavouriteCommand;
use \TelegramCustomWrapper\Events\Command\FeedbackCommand;
use \TelegramCustomWrapper\Events\Command\HelpCommand;
use \TelegramCustomWrapper\Events\Command\LocationCommand;
use \TelegramCustomWrapper\Events\Command\MessageCommand;
use \TelegramCustomWrapper\Events\Command\SettingsCommand;
use TelegramCustomWrapper\Events\Command\StartCommand;
use \TelegramCustomWrapper\Events\Command\UnknownCommand;
use \TelegramCustomWrapper\Events\Button\HelpButton;
use \TelegramCustomWrapper\Events\Button\FavouriteButton;
use \TelegramCustomWrapper\Events\Special\File;
use TelegramCustomWrapper\Events\Special\InlineQuery;
use \TelegramCustomWrapper\Events\Special\Photo;
use \unreal4u\TelegramAPI\Telegram;
use \unreal4u\TelegramAPI\TgLog;
use \unreal4u\TelegramAPI\HttpClientRequestHandler;

class TelegramCustomWrapper
{
	private $botToken;
	private $botName;

	private $tgLog;
	private $loop;

	public function __construct($botToken, $botName) {
		$this->botToken = $botToken;
		$this->botName = $botName;

		$this->loop = \React\EventLoop\Factory::create();
		$this->tgLog = new TgLog($botToken, new HttpClientRequestHandler($this->loop));
	}

	/**
	 * @param $updateData
	 * @return DebugCommand|File|HelpCommand|LocationCommand|MessageCommand|UnknownCommand|string|void
	 * @throws \Exception
	 */
	public function handleUpdate($updateData) {
		$update = new Telegram\Types\Update($updateData);
		if ($update->update_id === 0) { // default value
			throw new \Exception('Telegram webhook API data are missing!');
		}
		if ($update->edited_channel_post || $update->edited_message) {
			return 'Edit\'s are ignored';
		}
		if (TelegramHelper::isViaBot($update, TELEGRAM_BOT_NAME)) {
			return 'I will ignore my own via_bot (from inline) messages.';
		}
		if (TelegramHelper::isChosenInlineQuery($update)) {
			// @TODO implement ChosenInlineQuery handler
			return 'ChosenInlineQuery handler is not implemented';
		}
		if (TelegramHelper::isInlineQuery($update)) {
			return new InlineQuery($update);
		}

		$command = TelegramHelper::getCommand($update);
		/** @noinspection PhpUnusedLocalVariableInspection */
		$params = TelegramHelper::getParams($update);

		if (TelegramHelper::isButtonClick($update)) {
			$update->callback_query->from->username = $update->callback_query->from->username === '' ? null : $update->callback_query->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->callback_query->from->displayname = TelegramHelper::getDisplayName($update->callback_query->from);

			switch ($command ? mb_strtolower($command) : null) {
				case '/help':
					return new HelpButton($update);
					break;
				case FavouriteCommand::CMD:
					return new FavouriteButton($update);
					break;
					// @TODO log error, this should not happen. Edit: can happen if some command is no longer used (for example /stats was changed to /donor)
				default: // unknown
					return;
					break;
			}
		} elseif (TelegramHelper::isLocation($update)) {
			return new LocationCommand($update);
		} elseif (TelegramHelper::hasDocument($update)) {
			return new File($update);
		} elseif (TelegramHelper::hasPhoto($update)) {
			return new Photo($update);
		} else {
			$update->message->from->username = $update->message->from->username === '' ? null : $update->message->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->message->from->displayname = TelegramHelper::getDisplayName($update->message->from);

			switch ($command ? mb_strtolower($command) : null) {
				case '/start':
					return new StartCommand($update);
					break;
				case '/help':
					return new HelpCommand($update);
					break;
				case '/debug':
					return new DebugCommand($update);
					break;
				case '/settings':
					return new SettingsCommand($update);
					break;
				case FavouriteCommand::CMD:
					return new FavouriteCommand($update);
					break;
				case '/feedback':
					return new FeedbackCommand($update);
					break;
				case null: // message without command
					return new MessageCommand($update);
					break;
				default: // unknown command
					return new UnknownCommand($update);
					break;
			}
		}
	}
}
