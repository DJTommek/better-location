<?php

declare(strict_types=1);

namespace TelegramCustomWrapper;

use unreal4u\TelegramAPI\Telegram\Types\Update;

class TelegramHelper
{
	const API_URL = 'https://api.telegram.org';
	const MESSAGE_PREFIX = \Icons::LOCATION . ' <b>Better location</b> by @' . \Config::TELEGRAM_BOT_NAME . ':' . PHP_EOL;

	/**
	 * Get regex for command entity
	 *
	 * @param bool $strict Enforce containing bot's case in-sensitive username (set in \Config class). Valid commands:
	 * - true:  /command@BetterLocationBot, /command@BETTERlocationBOT
	 * - false: /command@BetterLocationBot, /command@betterLOCATIONbot, /command
	 * @return string command without bot's username if it contains
	 */
	public static function getCommandRegex(bool $strict) {
		$regex = '/^';
		$regex .= '(\/[a-z0-9_]+)';
		$regex .= '(?:@' . \Config::TELEGRAM_BOT_NAME . ')';
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

	public static function getDisplayName($tgfrom) {
		if ($tgfrom->username) {
			$displayName = '@' . $tgfrom->username;
		} else {
			$displayName = '';
			$displayName .= ($tgfrom->first_name || ''); // first_name probably fill be always filled
			$displayName .= ' ';
			$displayName .= ($tgfrom->last_name || ''); // might be empty
		}
		return trim(htmlentities($displayName));
	}

	public static function isButtonClick($update): bool {
		return (!empty($update->callback_query));
	}

	public static function isForward(Update $update): bool {
		return (!empty($update->message->forward_from));
	}

	public static function isInlineQuery(Update $update): bool {
		return (!empty($update->inline_query));
	}

	public static function isChosenInlineQuery(Update $update): bool {
		return (!empty($update->chosen_inline_result));
	}

	public static function isLocation($update): bool {
		return (!empty($update->message->location));
	}

	public static function hasDocument($update): bool {
		return (!empty($update->message->document));
	}

	public static function hasPhoto($update): bool {
		return (!empty($update->message->photo));
	}

	public static function isViaBot(Update $update, $botUsername = null): bool {
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

	public static function getFileUrl(string $token, string $path): string {
		return sprintf('%s/file/bot%s/%s', self::API_URL, $token, $path);
	}

	public static function isPM(Update $update): bool {
		if (self::isButtonClick($update)) {
			return $update->callback_query->from->id === $update->callback_query->message->chat->id;
		} else {
			return ($update->message->from->id === $update->message->chat->id);
		}
	}

	public static function getCommand($update, bool $strict = false): ?string {
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
		if ($command && preg_match(self::getCommandRegex($strict), $command, $matches)) {
			return $matches[1];
		} else {
			return null;
		}
	}

	public static function getParams($update): array {
		if (self::isButtonClick($update)) {
			$text = $update->callback_query->data;
		} else {
			$text = $update->message->text;
		}
		$params = explode(' ', $text);
		array_shift($params);
		return $params;
	}

	public static function generateStart(string $params) {
		return sprintf('https://t.me/%s?start=', \Config::TELEGRAM_BOT_NAME) . TelegramHelper::InlineTextEncode($params);
	}

	public static function InlineTextEncode(string $input): string {
		$input = trim($input);
		$input = base64_encode($input);
		$input = str_replace('=', '_', $input);
		$input = str_replace('+', '-', $input);
		$input = trim($input);
		return $input;
	}

	public static function InlineTextDecode(string $input): string {
		$input = trim($input);
		$input = str_replace('_', '=', $input);
		$input = str_replace('-', '+', $input);
		$input = base64_decode($input);
		$input = trim($input);
		return $input;
	}
}
