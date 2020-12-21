# BetterLocation

Simple but very smart Telegram bot for processing various types of location format and converting them to user-defined formats.<br>
Available publicly on [@BetterLocationBot](https://t.me/BetterLocationBot).

![@BetterLocationBot example](www/img/better-location-bot-example.png "@BetterLocationBot example")

## Requirements
- PHP webserver (written and tested with PHP 7.3)
- Database server (written and tested with MySQL 8 and MariaDB 10)
- Domain with SSL certificate (might be self-signed). Detailed requirements are described on [Telegram's webhook page](https://core.telegram.org/bots/webhooks).

## Installation
1. Download/clone [BetterLocation repository](https://github.com/DJTommek/better-location).
1. Install production dependencies via `composer install --no-dev` - you need [Composer](https://getcomposer.org/) to do that.
1. Update all `DB_*` and `TELEGRAM_*` constants in `data/config.local.php`.
1. Create database using [structure.sql](asset/sql/structure.sql) script.
1. **Optional**: In case you are not doing this installation directly on your (web)hosting, copy all files there now.
1. Register [bot webhook](https://core.telegram.org/bots/api#setwebhook) to your webserver via [set-webhook.php](www/admin/set-webhook.php). For detailed info, open [index.php](www/admin/index.php).

## Development and testing
1. Install development depenencies via `composer install --dev`.
1. Run [PHPStan](https://phpstan.org/) static analysis via `composer phpstan`.
1. Run [PHPUnit](https://phpunit.de/) tests via `composer test`.

Note: Some tests may be skipped if missing configuration (Glympse or What3Words)

---
*Based on the simple [DJTommek/php-template](https://github.com/DJTommek/php-template).*
