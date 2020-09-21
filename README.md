# BetterLocation

Simple but very smart Telegram bot for processing various types of location format and converting them to user-defined formats.<br>
Available publicly on [@BetterLocationBot](https://t.me/BetterLocationBot).

![@BetterLocationBot example](asset/better-location-bot-example.png "@BetterLocationBot example")

# Requirements
- PHP webserver (written and tested with PHP 7.3)
- Database server (written and tested with MySQL 8 and MariaDB 10)
- Domain with SSL certificate (might be self-signed). Detailed requirements are described [here](https://core.telegram.org/bots/webhooks).

## ⚠ Important note
MapyCZ link with a Place ID or Panorama ID is currently **NOT** working properly without an additional NodeJS server. Read [this page](src/nodejs/README.md) for more info.  


# Installation
1. Download/clone [BetterLocation repository](https://github.com/DJTommek/better-location). 
1. Rename `config.local.example.php` to `config.local.php` and update necessary details.<br>
Note: If you don't have a token for your bot, you need to get one first, see https://core.telegram.org/bots.
1. Create database using [structure.sql](asset/sql/structure.sql) script.
1. Run `composer install`.
1. **Optional**: In case you are not doing this installation directly on your (web)hosting, copy all files there now.
1. Register [bot webhook](https://core.telegram.org/bots/api#setwebhook) to your webserver by opening [set-webhook.php](./set-webhook.php) in a browser. For detailed info, open [index.php](./index.php).
1. **Optional but recommended**: Disable public access to all files except webhook.php, which has to be accessed from Telegram servers.

---
*Based on the simple [DJTommek/php-template](https://github.com/DJTommek/php-template).*
