<?php

class Factory
{
	private static $objects = array();

	static function Database(): Database {
		if (!isset(self::$objects['database'])) {
			self::$objects['database'] = new Database(DB_SERVER, DB_NAME, DB_USER, DB_PASS);
		}
		return self::$objects['database'];
	}

	static function Telegram(): TelegramCustomWrapper {
		if (!isset(self::$objects['telegram'])) {
			self::$objects['telegram'] = new TelegramCustomWrapper(TELEGRAM_BOT_TOKEN);
		}
		return self::$objects['telegram'];
	}
}