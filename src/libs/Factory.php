<?php

class Factory
{
	private static $objects = array();

	static function Database(): Database {
		if (!isset(self::$objects['database'])) {
			self::$objects['database'] = new Database(\Config::DB_SERVER, \Config::DB_NAME, \Config::DB_USER, \Config::DB_PASS);
		}
		return self::$objects['database'];
	}

	static function Telegram(): \TelegramCustomWrapper\TelegramCustomWrapper {
		if (!isset(self::$objects['telegram'])) {
			self::$objects['telegram'] = new \TelegramCustomWrapper\TelegramCustomWrapper(\Config::TELEGRAM_BOT_TOKEN, \Config::TELEGRAM_BOT_NAME);
		}
		return self::$objects['telegram'];
	}
}
