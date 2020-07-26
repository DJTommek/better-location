<?php

// List of your IP address for development
DEFINE('DEVELOPMENT_IPS', [
	'12.34.56.78',
]);

// Telegram bot token generated from BotFather: https://t.me/BotFather
DEFINE('TELEGRAM_BOT_TOKEN', '123456789:afsddfsggfergfgsadfdiswefqjdfbjfddt');
// Telegram bot name without @ prefix.
DEFINE('TELEGRAM_BOT_NAME', 'BetterLocationBot');
// Telegram webhook URL, which will automatically receive all events from bot (in this application it should lead to webhook.php)
DEFINE('TELEGRAM_WEBHOOK_URL', 'https://your-domain.com/better-location/webhook.php');

// API key to What3Word service https://developer.what3words.com/public-api
DEFINE('W3W_API_KEY', 'SOME_API_KEY');

// URL to NodeJS dummy server to handle generating payload and requests for MapyCZ place IDs. For more info see src/nodejs/README.md
//DEFINE('MAPY_CZ_DUMMY_SERVER_URL', 'http://localhost:3055'); // URL of your webserver (without trailing slash)
DEFINE('MAPY_CZ_DUMMY_SERVER_URL', null); // null to disable this feature and fallback to using inaccurate x and y coordinates from URL
// Request timeout
DEFINE('MAPY_CZ_DUMMY_SERVER_TIMEOUT', 5); // default 5

// If some input (URL) has multiple different locations, how far it has to be from main coordinate to add special line
// to notify, that these locations are too far away. Anything lower than this number will be removed from list
DEFINE('DISTANCE_IGNORE', 10); // distance in meters (int or float)
