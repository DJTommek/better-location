<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\General;
use Tracy\Debugger;
use Tracy\ILogger;

final class ZanikleObceCzService extends AbstractService
{
	const NAME = 'ZanikleObce.cz';

	const LINK = 'http://zanikleobce.cz';

	/**@throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			// http://www.zanikleobce.cz/index.php?menu=222&mpx=14.23444&mpy=48.59027
			return self::LINK . sprintf('/index.php?menu=222&mpx=%2$f&mpy=%1$f', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool
	{
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation
	{
		$originalUrl = $url;
		if (self::isDetailPageUrl($url)) {
			$url = self::getObecUrlFromDetail($url);
			if (is_null($url)) {
				// @TODO maybe should return null instead of error since not all pages has saved location
				throw new InvalidLocationException('No valid location found');
			}
		}
		$coords = self::getLocationFromPageObec($url);
		if ($coords) {
			return new BetterLocation($originalUrl, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from %s link %s.', self::NAME, $url));
		}
	}

	private static function isCorrectDomainUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['zanikleobce.cz', 'www.zanikleobce.cz']) &&
			isset($parsedUrl['query'])
		);
	}

	private static function isObecPageUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['query']['obec']) &&
			preg_match('/^[0-9]+$/', $parsedUrl['query']['obec'])
		);
	}

	private static function isDetailPageUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['query']['detail']) &&
			preg_match('/^[0-9]+$/', $parsedUrl['query']['detail'])
		);
	}

	public static function isUrl(string $url): bool
	{
		return self::isCorrectDomainUrl($url) && (self::isObecPageUrl($url) || self::isDetailPageUrl($url));
	}

	private static function getObecUrlFromDetail(string $url): ?string
	{
		try {
            $response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_ZANIKLE_OBCE_CZ)->run()->getBody();
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
//		if (!preg_match('/<DIV class="detail_popis"><BIG><B><A HREF="([^"]+)/', $response, $matches)) { // original matching but not matching all urls
		if (!preg_match('/HREF="([^"]+obec=[^"]+)"/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Detail page "%s" has no location.', $url));
		}
		return self::LINK . '/' . html_entity_decode($matches[1]);
	}

	private static function getLocationFromPageObec(string $url): ?array
	{
		try {
            $response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_ZANIKLE_OBCE_CZ)->run()->getBody();
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
		if (!preg_match('/<a href=\"(https:\/\/mapy\.cz\/[^"]+)/', $response, $matches)) {  // might be multiple matches, return first occured
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on obec page "%s" are missing.', $url));
		}
		$mapyCzUrl = $matches[1];
		if (MapyCzService::isNormalUrl($mapyCzUrl) === false) {
			throw new InvalidLocationException(sprintf('Parsed Mapy.cz URL from "%s" is not valid.', $url));
		}
		$mapyCzLocation = MapyCzService::parseUrl($mapyCzUrl);
		return [
			$mapyCzLocation->getLat(),
			$mapyCzLocation->getLon(),
		];
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
