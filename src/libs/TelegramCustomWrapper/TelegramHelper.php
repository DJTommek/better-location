<?php

declare(strict_types=1);

namespace TelegramCustomWrapper;

class TelegramHelper
{
	const API_URL = 'https://api.telegram.org';
	const MESSAGE_PREFIX = \Icons::LOCATION . ' @' . TELEGRAM_BOT_NAME . ':' . PHP_EOL;

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

	public static function isLocation($update): bool {
		return (!empty($update->message->location));
	}

	public static function hasDocument($update): bool {
		return (!empty($update->message->document));
	}

	public static function hasPhoto($update): bool {
		return (!empty($update->message->photo));
	}

	public static function getFileUrl(string $token, string $path): string {
		return sprintf('%s/file/bot%s/%s', self::API_URL, $token, $path);
	}

	public static function isPM($update): bool {
		if (self::isButtonClick($update)) {
			$message = $update->callback_query->message;
		} else {
			$message = $update->message;
		}
		return ($message->from->id === $message->chat->id);
	}

	public static function getCommand($update): ?string {
		if (self::isButtonClick($update)) {
			$fullCommand = $update->callback_query->data;
			return explode(' ', $fullCommand)[0];
		} else {
			foreach ($update->message->entities as $entity) {
				if ($entity->offset === 0 && $entity->type === 'bot_command') {
					return mb_strcut($update->message->text, $entity->offset, $entity->length);
				}
			}
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
}