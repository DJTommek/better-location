<?php declare(strict_types=1);

namespace App\TelegramCustomWrapper;

use App\Config;
use App\Utils\Strict;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Nette\Utils\Strings;
use unreal4u\TelegramAPI\Telegram;
use unreal4u\TelegramAPI\Telegram\Types\Chat;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;
use unreal4u\TelegramAPI\Telegram\Types\Update;
use unreal4u\TelegramAPI\Telegram\Types\User;

class TelegramHelper
{
	public const API_URL = 'https://api.telegram.org';
	public const WEBHOOK_SECRET_TOKEN_HEADER_KEY = 'X-Telegram-Bot-Api-Secret-Token';
	public const INLINE_KEYBOARD_MAX_BUTTON_PER_ROW = 8;
	/**
	 * (Almost) invisible character, see usage for more info.
	 *
	 * @author https://emptycharacter.com/
	 */
	public const INVISIBLE_CHARACTER = '&#8202';
	/**
	 * Newline character
	 */
	public const NEW_LINE = "\n";

	/**
	 * Newline character (shortcut alias)
	 */
	public const NL = self::NEW_LINE;

	/**
	 * Generate (almost) invisible link, that is still valid - useful for Telegram's automatic generating preview from links
	 */
	public static function invisibleLink(UrlImmutable|Url|string $url): string
	{
		return sprintf('<a href="%s" target="_blank">%s</a>', $url, self::INVISIBLE_CHARACTER);
	}

	/**
	 * Get regex for command entity
	 *
	 * @param bool $strict Enforce containing bot's case in-sensitive username (set in \Config class). Valid commands:
	 * - true:  /command@BetterLocationBot, /command@BETTERlocationBOT
	 * - false: /command@BetterLocationBot, /command@betterLOCATIONbot, /command
	 * @return string command without bot's username if it contains
	 */
	public static function getCommandRegex(bool $strict): string
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
	const CHAT_WRITE_FORBIDDEN = 'Bad Request: CHAT_WRITE_FORBIDDEN';
	const CHANNEL_WRITE_FORBIDDEN = 'Bad Request: need administrator rights in the channel chat';
	const REPLIED_MESSAGE_NOT_FOUND = 'Bad Request: replied message not found';
	const BOT_BLOCKED_BY_USER = 'Forbidden: bot was blocked by the user';
	const NOT_ENOUGH_RIGHTS_SEND_TEXT = 'Bad Request: not enough rights to send text messages to the chat';

	/**
	 * @param User|Chat $from If chat is private, User and Chat have similar properties
	 * @return string
	 */
	public static function getUserDisplayname($from): string
	{
		$displayName = $from->username ? ('@' . $from->username) : ($from->first_name . ' ' . $from->last_name);
		return trim(htmlspecialchars($displayName));
	}

	public static function getChatDisplayname(Chat $chat): string
	{
		if ($chat->title) {
			return trim(htmlspecialchars($chat->title));
		} else {
			return self::getUserDisplayname($chat);
		}
	}

	public static function getMessage(Update $update, bool $allowEdits = false): ?Telegram\Types\Message
	{
		if (self::isMessage($update)) {
			assert($update->message !== null);
			return $update->message;
		}

		if (self::isChannelPost($update)) {
			assert($update->channel_post !== null);
			return $update->channel_post;
		}

		if ($allowEdits && self::isMessageEdit($update)) {
			assert($update->edited_message !== null);
			return $update->edited_message;
		}

		if ($allowEdits && self::isChannelPostEdit($update)) {
			assert($update->edited_channel_post !== null);
			return $update->edited_channel_post;
		}

		return null;
	}

	public static function isButtonClick(Update $update): bool
	{
		return $update->callback_query !== null;
	}

	/**
	 * @return bool False if clicked on button in shared in message created from inline (in "via @BotName")
	 */
	public static function hasButtonMessage(Update $update): bool
	{
		return $update->callback_query?->message !== null;
	}

	public static function isForward(Update $update): bool
	{
		return self::getMessage($update)?->forward_from !== null;
	}

	public static function isInlineQuery(Update $update): bool
	{
		return $update->inline_query !== null;
	}

	public static function isChosenInlineQuery(Update $update): bool
	{
		return $update->chosen_inline_result !== null;
	}

	/** @param bool $isLive Additionally check if this is live location */
	public static function isLocation(
		Update $update,
		bool $isLive = false,
		bool $allowEdits = true
	): bool
	{
		$location = self::getMessage($update, $allowEdits)->location ?? null;
		if ($location === null) {
			return false;
		}

		if ($isLive === true && empty($location->live_period)) {
			return false;
		} else {
			return true;
		}
	}

	public static function isVenue(Update $update): bool
	{
		return self::getMessage($update)?->venue !== null;
	}

	public static function isEdit(Update $update): bool
	{
		return self::isMessageEdit($update) || self::isChannelPostEdit($update);
	}

	public static function isMessage(Update $update): bool
	{
		return $update->message !== null;
	}

	public static function isMessageEdit(Update $update): bool
	{
		return $update->edited_message !== null;
	}

	public static function isChannelPost(Update $update): bool
	{
		return $update->channel_post !== null;
	}

	public static function isChannelPostEdit(Update $update): bool
	{
		return $update->edited_channel_post !== null;
	}

	public static function hasDocument(Update $update): bool
	{
		return self::getMessage($update)?->document !== null;
	}

	public static function hasContact(Update $update): bool
	{
		return self::getMessage($update)?->contact !== null;
	}

	public static function hasPhoto(Update $update): bool
	{
		return (self::getMessage($update)?->photo ?? []) !== [];
	}

	/**
	 * @param string|null $botUsername Message sender must match this username
	 */
	public static function isViaBot(Update $update, ?string $botUsername = null): bool
	{
		$viaBot = self::getMessage($update)?->via_bot;
		if ($viaBot === null) {
			return false;
		}

		if ($botUsername === null) {
			return true;
		}

		assert($viaBot instanceof User);
		if (mb_strtolower($viaBot->username) === mb_strtolower($botUsername)) {
			return true;
		}

		return false;
	}

	public static function chatCreated(Update $update): bool
	{
		return ($update->message?->group_chat_created ?? false) === true;
	}

	/**
	 * @param string|null $username Added user must match this username
	 */
	public static function addedToChat(Update $update, ?string $username = null): bool
	{
		if (self::chatCreated($update)) {
			return true; // User added while creating group
		}

		$newChatMembersList = $update->message?->new_chat_members ?? [];
		if ($newChatMembersList === []) {
			return false;
		}

		if ($username === null) {
			return true; // Any user was added to already group
		}

		foreach ($newChatMembersList as $newChatMember) {
			assert($newChatMember instanceof User);
			if (mb_strtolower($newChatMember->username) === mb_strtolower($username)) {
				return true; // Specific user added to already created group
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
		if (self::isChannelPost($update)) {
			return false;
		}

		if (self::isButtonClick($update)) {
			// If the button was attached to a message sent via the bot (in inline mode),
			// the field inline_message_id will be present. (https://core.telegram.org/bots/api#callbackquery)
			if ($update->callback_query->inline_message_id) {
				return null;
			} else {
				return $update->callback_query->from->id === $update->callback_query->message->chat->id;
			}
		}

		return ($update->message->from->id === $update->message->chat->id);
	}

	public static function getCommand(Update $update, bool $strict = false): ?string
	{
		$command = null;
		if (self::isButtonClick($update)) {
			$fullCommand = $update->callback_query->data;
			$command = explode(' ', $fullCommand)[0];
		} else {
			$entities = $update->message?->entities ?? $update->channel_post?->entities ?? [];
			$text = $update->message?->text ?? $update->channel_post?->text ?? null;
			assert($text !== null);
			foreach ($entities as $entity) {
				if ($entity->offset === 0 && $entity->type === 'bot_command') {
					$command = mb_strcut($text, $entity->offset, $entity->length);
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
			$text = self::getMessage($update)->text;
		}
		assert($text !== null);
		$params = explode(' ', $text);
		array_shift($params);
		return $params;
	}

	public static function generateStart(string $params): string
	{
		return sprintf('https://t.me/%s?start=', Config::TELEGRAM_BOT_NAME) . self::InlineTextEncode($params);
	}

	public static function generateStartLocation(float $lat, float $lon): string
	{
		return sprintf('https://t.me/%s?start=%d_%d', Config::TELEGRAM_BOT_NAME, $lat * 1000000, $lon * 1000000);
	}

	public static function InlineTextEncode(string $input): string
	{
		$input = trim($input);
		$input = base64_encode($input);
		$input = str_replace('=', '_', $input);
		$input = str_replace('+', '-', $input);
		return trim($input);
	}

	public static function InlineTextDecode(string $input): string
	{
		$input = trim($input);
		$input = str_replace('_', '=', $input);
		$input = str_replace('-', '+', $input);
		$input = base64_decode($input);
		return trim($input);
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
		foreach (\App\Utils\Utils::getUrls($message) as $url) {
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
				if (Strict::isUrl($entityContent)) {
					$text = str_replace($entityContent, str_repeat('|', $entity->length), $text);
				}
			}
		}
		return $text;
	}

	public static function loginUrlButton(string $text, UrlImmutable $redirectUrl = null): Telegram\Types\Inline\Keyboard\Button
	{
		return new Telegram\Types\Inline\Keyboard\Button([
			'text' => $text,
			'login_url' => new Telegram\Types\LoginUrl([
				'url' => Config::getLoginUrl($redirectUrl)->getAbsoluteUrl(),
			]),
		]);
	}

	public static function userLink(string $username): string
	{
		return 'https://t.me/' . ltrim($username, '@');
	}

	public static function userLinkTag(string $username): string
	{
		if (Strings::startsWith($username, '@') === false) {
			$username = '@' . $username;
		}
		return sprintf('<a href="%s" target="_blank">%s</a>', self::userLink($username), $username);
	}

	public static function isAdmin(Telegram\Types\ChatMember $chatMember): bool
	{
		return in_array($chatMember->status, ['creator', 'administrator'], true);
	}
}
