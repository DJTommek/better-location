<?php declare(strict_types=1);

namespace App;

class Factory
{
	private static $objects = array();

	static function Database(): Database
	{
		if (!isset(self::$objects['database'])) {
			self::$objects['database'] = new Database(Config::DB_SERVER, Config::DB_NAME, Config::DB_USER, Config::DB_PASS);
		}
		return self::$objects['database'];
	}

	static function Telegram(): \App\TelegramCustomWrapper\TelegramCustomWrapper
	{
		if (!isset(self::$objects['telegram'])) {
			self::$objects['telegram'] = new \App\TelegramCustomWrapper\TelegramCustomWrapper(Config::TELEGRAM_BOT_TOKEN, Config::TELEGRAM_BOT_NAME);
		}
		return self::$objects['telegram'];
	}

	static function WhatThreeWords(): \What3words\Geocoder\Geocoder
	{
		if (!isset(self::$objects['w3w'])) {
			self::$objects['w3w'] = new \What3words\Geocoder\Geocoder(Config::W3W_API_KEY);
		}
		return self::$objects['w3w'];
	}

	static function Glympse(): \DJTommek\GlympseApi\GlympseApi
	{
		if (!isset(self::$objects['glympse'])) {
			self::$objects['glympse'] = new \DJTommek\GlympseApi\GlympseApi(Config::GLYMPSE_API_USERNAME, Config::GLYMPSE_API_PASSWORD, Config::GLYMPSE_API_KEY);
		}
		return self::$objects['glympse'];
	}

	static function Geocaching(): \App\Geocaching\Client
	{
		if (!isset(self::$objects['geocaching'])) {
			self::$objects['geocaching'] = new \App\Geocaching\Client(Config::GEOCACHING_COOKIE);
			self::$objects['geocaching']->setCache(Config::CACHE_TTL_GEOCACHING_API);
		}
		return self::$objects['geocaching'];
	}

	static function Foursquare(): \App\Foursquare\Client
	{
		if (!isset(self::$objects['foursquare'])) {
			self::$objects['foursquare'] = new \App\Foursquare\Client(Config::FOURSQUARE_CLIENT_ID, Config::FOURSQUARE_CLIENT_SECRET);
			self::$objects['foursquare']->setCache(Config::CACHE_TTL_FOURSQUARE_API);
		}
		return self::$objects['foursquare'];
	}

	static function IngressLanchedRu(): \App\IngressLanchedRu\Client
	{
		if (!isset(self::$objects['ingressLanchedRu'])) {
			self::$objects['ingressLanchedRu'] = new \App\IngressLanchedRu\Client();
			self::$objects['ingressLanchedRu']->setCache(Config::CACHE_TTL_INGRESS_LANCHED_RU_API);
		}
		return self::$objects['ingressLanchedRu'];
	}

	static function IngressMosaic(): \App\IngressMosaic\Client
	{
		if (!isset(self::$objects['ingressMosaic'])) {
			self::$objects['ingressMosaic'] = new \App\IngressMosaic\Client(Config::INGRESS_MOSAIC_COOKIE_XSRF, Config::INGRESS_MOSAIC_COOKIE_SESSION);
			self::$objects['ingressMosaic']->setCache(Config::CACHE_TTL_INGRESS_MOSAIC);
		}
		return self::$objects['ingressMosaic'];
	}
}
