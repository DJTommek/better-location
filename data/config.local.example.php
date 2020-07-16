<?php
// Set database connection settings
DEFINE('DB_SERVER', 'localhost');
DEFINE('DB_USER', 'dbuser');
DEFINE('DB_PASS', 'dbpass');
DEFINE('DB_NAME', 'dbschema');

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
