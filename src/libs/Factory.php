<?php declare(strict_types=1);

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

	static function WhatThreeWords():\What3words\Geocoder\Geocoder {
		if (!isset(self::$objects['w3w'])) {
			self::$objects['w3w'] = new \What3words\Geocoder\Geocoder(\Config::W3W_API_KEY);
		}
		return self::$objects['w3w'];
	}

	static function Glympse():\GlympseApi\Glympse {
		if (!isset(self::$objects['glympse'])) {
			self::$objects['glympse'] = new GlympseApi\Glympse(\Config::GLYMPSE_API_USERNAME, \Config::GLYMPSE_API_PASSWORD, \Config::GLYMPSE_API_KEY);
		}
		return self::$objects['glympse'];
	}
}
