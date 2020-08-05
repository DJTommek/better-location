# BetterLocation

Simple but very smart Telegram bot for processing various types of location and converting to user-defined formats.

Available publicly on [@BetterLocationBot](https://t.me/BetterLocationBot).

# Requirements
- PHP webserver (written and tested with PHP 7.3)
- Domain with SSL certificate (might be self-signed). Full detailed requirements are described [here](https://core.telegram.org/bots/webhooks).

## ⚠ Important note
MapyCZ link with Place ID or Panorama ID currently is **NOT** working properly without running additional NodeJS server, read [this page](src/nodejs/README.md) for more info.  


# Installation
1. Download/clone [BetterLocation repository](https://github.com/DJTommek/better-location). 
1. Rename `config.local.example.php` to `config.local.php` and update necessary details.<br>
Note: If you still don't have token for your bot, you need to get one first, see https://core.telegram.org/bots
1. Run `composer install`.
1. **Optional**: In case you do not doing this installation directly on your (web)hosting, copy all files there now.
1. Register [bot webhook](https://core.telegram.org/bots/api#setwebhook) to your webserver by opening [set-webhook.php](./set-webhook.php) in browser. For detailed info open [index.php](./index.php).
1. **Optional but recommended**: Disable public access to all files except webhook.php which has to be accessed from Telegram servers. 

---
*Based on simple [DJTommek/php-template](https://github.com/DJTommek/php-template).*