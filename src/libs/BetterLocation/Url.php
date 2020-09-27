<?php

namespace BetterLocation;

use Utils\General;

class Url
{
	/**
	 * @param $url
	 * @return mixed|null
	 * @throws \Exception
	 */
	public static function getRedirectUrl($url): ?string {
		$headers = General::getHeaders($url);
		return $headers['location'] ?? null;
	}
}
