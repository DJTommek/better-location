<?php declare(strict_types=1);

/**
 * Warning: Never update this file directly, always update config.local.php in data folder!
 *
 * You can override any constant from this file if you want.
 */
class DefaultConfig
{
	const FOLDER_DATA = __DIR__;

	const DB_SERVER = 'localhost';
	const DB_USER = 'dbuser';
	const DB_PASS = 'dbpass';
	const DB_NAME = 'dbschema';

	const DEVELOPMENT_IPS = [
		'12.34.56.78',
	];
	/** @var ?string Put your email if you want to receive emails about errors and exceptions. See https://tracy.nette.org/guide for more info. null to disable */
	const TRACY_DEBUGGER_EMAIL = null;  // null to disable
	// const TRACY_DEBUGGER_EMAIL = 'admin@your-domain.com';

	/** @var string Telegram bot token generated from BotFather: https://t.me/BotFather */
	const TELEGRAM_BOT_TOKEN = '123456789:abcdefghijklmnopqrstuvwxyzabcdefghi';
	/** @var string Telegram bot name without @ prefix. */
	const TELEGRAM_BOT_NAME = 'BetterLocationBot';
	/** @var string Telegram webhook URL, which will automatically receive all events from bot (in this application it should lead to webhook.php) */
	const TELEGRAM_WEBHOOK_URL = 'https://your-domain.com/better-location/webhook.php';
	/** @var int Telegram webhook URL, which will automatically receive all events from bot (in this application it should lead to webhook.php) */
	const TELEGRAM_INLINE_CACHE = 300; // https://core.telegram.org/bots/api#answerinlinequery cache_time attribute (default 300)

	/** @var ?string API Key for using Google Place API: https://developers.google.com/places/web-service/search null to disable */
	const GOOGLE_PLACE_API_KEY = null;
	//const GOOGLE_PLACE_API_KEY = 'someRandomGeneratedApiKeyFromGoogleCloudPlatform';

	/** @var ?string API key to What3Word service https://developer.what3words.com/public-api or null to disable */
	const W3W_API_KEY = null;

	/**
	 * URL to NodeJS dummy server to handle generating payload and requests for MapyCZ place IDs. For more info see src/nodejs/README.md
	 * @var ?string URL of your webserver (without trailing slash) or null to disable and fallback to using inaccurate x and y coordinates from URL.
	 */
	const MAPY_CZ_DUMMY_SERVER_URL = null;
	// Request timeout
	/** @var int */
	const MAPY_CZ_DUMMY_SERVER_TIMEOUT = 5;

	/**
	 * If some input (URL) has multiple different locations, how far it has to be from main coordinate to add special line
	 * to notify, that these locations are too far away. Anything lower than this number will be removed from collection
	 *
	 * @var int|float distance in meters
	 */
	const DISTANCE_IGNORE = 10;

	const DATE_FORMAT = 'Y-m-d';
	const TIME_FORMAT = 'H:i:s';
	const DATETIME_FORMAT = self::DATE_FORMAT . ' ' . self::TIME_FORMAT;
	const DATETIME_FORMAT_ZONE = self::DATETIME_FORMAT . 'T';
}
