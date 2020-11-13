<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\Icons;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User;

class TelegramHelper
{
	const API_URL = 'https://api.telegram.org';
	const MESSAGE_PREFIX = Icons::LOCATION . ' <b>Better location</b> by @' . Config::TELEGRAM_BOT_NAME . ':' . PHP_EOL;

	/**
	 * Get regex for command entity
	 *
	 * @param bool $strict Enforce containing bot's case in-sensitive username (set in \Config class). Valid commands:
	 * - true:  /command@BetterLocationBot, /command@BETTERlocationBOT
	 * - false: /command@BetterLocationBot, /command@betterLOCATIONbot, /command
	 * @return string command without bot's username if it contains
	 */
	public static function getCommandRegex(bool $strict)
	{
		$regex = '/^';
		$regex .= '(\/[a-z0-9_]+)';
		$regex .= '(?:@' . Config::TELEGRAM_BOT_NAME . ')';
		if ($strict === false) {
			$regex .= '?'; // make last part of regex optional
		}
		$regex .= '$/i';
		return $regex;
	}

	// @TODO Move CHAT_ACTION_* to some ENUM
	const CHAT_ACTION_TYPING = 'typing';
	const CHAT_ACTION_UPLOAD_PHOTO = 'upload_photo';
	const CHAT_ACTION_RECORD_VIDEO = 'record_video';
	const CHAT_ACTION_UPLOAD_VIDEO = 'upload_video';
	const CHAT_ACTION_RECORD_AUDIO = 'record_audio';
	const CHAT_ACTION_UPLOAD_AUDIO = 'upload_audio';
	const CHAT_ACTION_UPLOAD_DOCUMENT = 'upload_document';
	const CHAT_ACTION_FIND_LOCATION = 'find_location';
	const CHAT_ACTION_RECORD_VIDEO_NOTE = 'record_video_note';
	const CHAT_ACTION_UPLOAD_VIDEO_NOTE = 'upload_video_note';

	const NOT_CHANGED = 'Bad Request: message is not modified: specified new message content and reply markup are exactly the same as a current content and reply markup of the message';
	const TOO_OLD = 'Bad Request: query is too old and response timeout expired or query ID is invalid';
	const MESSAGE_TO_EDIT_DELETED = 'Bad Request: message to edit not found';

	/**
	 * @param User|Chat $from If chat is private, User and Chat have similar properties
	 * @return string
	 */
	public static function getUserDisplayname($from): string
	{
		$displayName = $from->username ? ('@' . $from->username) : ($from->first_name . ' ' . $from->last_name);
		return trim(htmlentities($displayName));
	}

	public static function getChatDisplayname(Chat $chat): string
	{
		if ($chat->title) {
			return trim(htmlentities($chat->title));
		} else {
			return self::getUserDisplayname($chat);
		}
	}

	public static function isButtonClick($update): bool
	{
		return (!empty($update->callback_query));
	}

	public static function isForward(Update $update): bool
	{
		return (!empty($update->message->forward_from));
	}

	public static function isInlineQuery(Update $update): bool
	{
		return (!empty($update->inline_query));
	}

	public static function isChosenInlineQuery(Update $update): bool
	{
		return (!empty($update->chosen_inline_result));
	}

	/** @param bool $live is location live */
	public static function isLocation(Update $update, bool $live = false): bool
	{
		$location = $update->message->location ?? $update->edited_message->location ?? null;
		if ($location) {
			if ($live === true && empty($location->live_period)) {
				return false;
			} else {
				return true;
			}
		} else {
			return false;
		}
	}

	public static function isEdit(Update $update): bool
	{
		return ($update->edited_channel_post || $update->edited_message);
	}

	public static function isChannel(Update $update): bool
	{
		return !empty($update->channel_post);
	}

	public static function hasDocument($update): bool
	{
		return (!empty($update->message->document));
	}

	public static function hasPhoto($update): bool
	{
		return (!empty($update->message->photo));
	}

	public static function isViaBot(Update $update, ?string $botUsername = null): bool
	{
		if (empty($update->message->via_bot) === false) {
			if (is_null($botUsername)) {
				return true;
			} else if (mb_strtolower($update->message->via_bot->username) === mb_strtolower($botUsername)) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}

	public static function chatCreated(Update $update): bool
	{
		return ($update->message && $update->message->group_chat_created);
	}

	public static function addedToChat(Update $update, ?string $username = null): bool
	{
		if (self::chatCreated($update)) {
			return true; // User added while creating group
		} else if (count($update->message->new_chat_members ?? []) > 0) {
			if (is_null($username)) {
				return true; // Any user was added to already group
			} else {
				foreach ($update->message->new_chat_members as $newChatMember) {
					if (mb_strtolower($newChatMember->username) === mb_strtolower($username)) {
						return true; // Specific user added to already created group
					}
				}
			}
		}
		return false;
	}

	public static function getFileUrl(string $token, string $path): string
	{
		return sprintf('%s/file/bot%s/%s', self::API_URL, $token, $path);
	}

	/** @return bool|null null if unknown (eg. clicked on button in via_bot message) */
	public static function isPM(Update $update): ?bool
	{
		if (self::isButtonClick($update)) {
			// If the button was attached to a message sent via the bot (in inline mode),
			// the field inline_message_id will be present. (https://core.telegram.org/bots/api#callbackquery)
			if ($update->callback_query->inline_message_id) {
				return null;
			} else {
				return $update->callback_query->from->id === $update->callback_query->message->chat->id;
			}
		} else {
			return ($update->message->from->id === $update->message->chat->id);
		}
	}

	public static function getCommand(Update $update, bool $strict = false): ?string
	{
		$command = null;
		if (self::isButtonClick($update)) {
			$fullCommand = $update->callback_query->data;
			$command = explode(' ', $fullCommand)[0];
		} else {
			foreach ($update->message->entities as $entity) {
				if ($entity->offset === 0 && $entity->type === 'bot_command') {
					$command = mb_strcut($update->message->text, $entity->offset, $entity->length);
					break;
				}
			}
		}
		if (self::isPM($update) === true) {
			$strict = false; // there is no need to write bot username since there is one to one
		}
		if (self::isButtonClick($update)) {
			$strict = false; // clicking on button is going only to bot, who created it
		}
		if ($command && preg_match(self::getCommandRegex($strict), $command, $matches)) {
			return mb_strtolower($matches[1]);
		} else {
			return null;
		}
	}

	public static function getParams($update): array
	{
		if (self::isButtonClick($update)) {
			$text = $update->callback_query->data;
		} else {
			$text = $update->message->text;
		}
		$params = explode(' ', $text);
		array_shift($params);
		return $params;
	}

	public static function generateStart(string $params)
	{
		return sprintf('https://t.me/%s?start=', Config::TELEGRAM_BOT_NAME) . self::InlineTextEncode($params);
	}

	public static function InlineTextEncode(string $input): string
	{
		$input = trim($input);
		$input = base64_encode($input);
		$input = str_replace('=', '_', $input);
		$input = str_replace('+', '-', $input);
		$input = trim($input);
		return $input;
	}

	public static function InlineTextDecode(string $input): string
	{
		$input = trim($input);
		$input = str_replace('_', '=', $input);
		$input = str_replace('-', '+', $input);
		$input = base64_decode($input);
		$input = trim($input);
		return $input;
	}

	/**
	 * Return entity content in UTF-8 (currently supported only for "text_link" and "url")
	 *
	 * @author https://stackoverflow.com/questions/49035310/php-telegram-bot-extract-url-in-utf-16-code-units/49430787
	 * @see https://stackoverflow.com/questions/30604427/php-length-of-string-containing-emojis-special-chars
	 */
	public static function getEntityContent(string $message, MessageEntity $entity): string
	{
		switch ($entity->type) {
			case 'url':
				$message16 = mb_convert_encoding($message, 'UTF-16', 'UTF-8');
				$entityContent16 = substr($message16, $entity->offset * 2, $entity->length * 2);
				return mb_convert_encoding($entityContent16, 'UTF-8', 'UTF-16');
			case 'text_link':
				return $entity->url;
			default:
				throw new \InvalidArgumentException(sprintf('Processing entity content of type "%s" is currently not supported.', $entity->type));
		}
	}

	/**
	 * Simulate Telegram message by creating URL entities (currently only URLs)
	 *
	 * @return MessageEntity[]
	 * @see self::getEntityContent()
	 */
	public static function generateEntities(string $message): array
	{
		$message16 = mb_convert_encoding($message, 'UTF-16', 'UTF-8');
		$entities = [];
		foreach (\App\Utils\General::getUrls($message) as $url) {
			$url16 = mb_convert_encoding($url, 'UTF-16', 'UTF-8');
			$entity = new MessageEntity();
			$entity->type = 'url';
			$entity->offset = strpos($message16, $url16) / 2;
			$entity->length = strlen($url16) / 2;
			$entities[] = $entity;
		}
		return $entities;
	}

	/**
	 * Remove all URLs from Telegram message according entities.
	 * URLs will be replaced with ||| proper length to keep entity offset and length valid eg.:
	 * 'Hello https://t.me/ here!'
	 * ->
	 * 'Hello ||||||||||||| here!'
	 *
	 * @param string $text
	 * @param MessageEntity[] $entities
	 * @return string
	 */
	public static function getMessageWithoutUrls(string $text, array $entities): string
	{
		foreach (array_reverse($entities) as $entity) {
			if ($entity->type === 'url') {
				$entityContent = TelegramHelper::getEntityContent($text, $entity);
				if (\App\BetterLocation\Url::isTrueUrl($entityContent)) {
					$text = str_replace($entityContent, str_repeat('|', $entity->length), $text);
				}
			}
		}
		return $text;
	}
}
