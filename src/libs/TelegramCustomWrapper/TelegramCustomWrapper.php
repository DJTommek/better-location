<?php

declare(strict_types=1);

namespace TelegramCustomWrapper;

use \TelegramCustomWrapper\Events\Command\DebugCommand;
use \TelegramCustomWrapper\Events\Command\HelpCommand;
use \TelegramCustomWrapper\Events\Command\LocationCommand;
use \TelegramCustomWrapper\Events\Command\MessageCommand;
use \TelegramCustomWrapper\Events\Command\PhotoCommand;
use \TelegramCustomWrapper\Events\Command\UnknownCommand;
use TelegramCustomWrapper\Events\Special\File;
use unreal4u\TelegramAPI\Telegram;
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
	 * @return DebugCommand|File|HelpCommand|LocationCommand|MessageCommand|PhotoCommand|UnknownCommand|string|void
	 * @throws \Exception
	 */
	public function handleUpdate($updateData) {
		$update = new Telegram\Types\Update($updateData);
		if ($update->update_id === 0) {
			throw new \Exception('Telegram webhook API data are missing! This page should be requested only from Telegram servers via webhook.');
		}
		if ($update->edited_channel_post || $update->edited_message) {
			return 'Edit\'s are ignored';
		}

		$command = TelegramHelper::getCommand($update);
		/** @noinspection PhpUnusedLocalVariableInspection */
		$params = TelegramHelper::getParams($update);

		if (TelegramHelper::isButtonClick($update)) {
			$update->callback_query->from->username = $update->callback_query->from->username === '' ? null : $update->callback_query->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->callback_query->from->displayname = TelegramHelper::getDisplayName($update->callback_query->from);

			switch ($command ? mb_strtolower($command) : null) {
				default: // unknown
					// @TODO log error, this should not happen. Edit: can happen if some command is no longer used (for example /stats was changed to /donor)
					return;
					break;
			}
		} elseif (TelegramHelper::isLocation($update)) {
			return new LocationCommand($update, $this->tgLog, $this->loop);
		} elseif (TelegramHelper::hasDocument($update)) {
			return new File($update, $this->tgLog, $this->loop);
		} elseif (TelegramHelper::hasPhoto($update)) {
			return new PhotoCommand($update, $this->tgLog, $this->loop);
		} else {
			$update->message->from->username = $update->message->from->username === '' ? null : $update->message->from->username;
			/** @noinspection PhpUndefinedFieldInspection */
			$update->message->from->displayname = TelegramHelper::getDisplayName($update->message->from);

			switch ($command ? mb_strtolower($command) : null) {
				case '/start':
				case '/help':
					return new HelpCommand($update, $this->tgLog, $this->loop);
					break;
				case '/debug':
					return new DebugCommand($update, $this->tgLog, $this->loop);
					break;
				case null: // message without command
					return new MessageCommand($update, $this->tgLog, $this->loop);
					break;
				default: // unknown command
					return new UnknownCommand($update, $this->tgLog, $this->loop);
					break;
			}
		}
	}
}
