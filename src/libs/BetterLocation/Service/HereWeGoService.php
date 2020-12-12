<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\General;
use Tracy\Debugger;
use Tracy\ILogger;

final class HereWeGoService extends AbstractService
{
	const NAME = 'HERE WeGo';
	const NAME_SHORT = 'HERE';

	const LINK = 'https://wego.here.com';
	const LINK_SHARE = 'https://share.here.com';

	const RE_COORDS_IN_MAP = '/(?:[,:\/]|^)(-?[0-9]{1,2}\.[0-9]{1,})[,\/](-?[0-9]{1,3}\.[0-9]{1,})(?:[,\/]|$)/';

	const TYPE_MAP = 'Map center';
	const TYPE_PLACE_COORDS = 'Place coords';
	const TYPE_PLACE_SHARE = 'Place share';
	const TYPE_PLACE_ORIGINAL_ID = 'Place';

	public static function getConstants(): array
	{
		return [
			self::TYPE_PLACE_ORIGINAL_ID,
			self::TYPE_PLACE_SHARE,
			self::TYPE_PLACE_COORDS,
			self::TYPE_MAP,
		];
	}

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @see https://developer.here.com/documentation/deeplink-web/dev_guide/topics/key-concepts.html
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-route.html
			return self::LINK_SHARE . sprintf('/r/%1$f,%2$f', $lat, $lon);
		} else { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-location.html
			return self::LINK_SHARE . sprintf('/l/%1$f,%2$f?p=yes', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool
	{
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoords(string $url): BetterLocation
	{
		throw new NotImplementedException('Parsing single coordinate is not supported. Use parseMultipleCoords() instead.');
	}

	public static function isShortUrl(string $url): bool
	{
		$parsedUrl = parse_url($url);
		if (isset($parsedUrl['host']) === false) {
			return false;
		}
		$allowedHosts = [
			'her.is',
		];
		return in_array($parsedUrl['host'], $allowedHosts);
	}

	public static function isNormalUrl(string $url): bool
	{
		$parsedUrl = parse_url($url);
		if (isset($parsedUrl['host']) === false) {
			return false;
		}
		$allowedHosts = [
			'share.here.com',
			'wego.here.com',
		];
		return in_array($parsedUrl['host'], $allowedHosts);
	}

	public static function isUrl(string $url): bool
	{
		return self::isShortUrl($url) || self::isNormalUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocationCollection
	 * @throws InvalidLocationException
	 */
	public static function parseUrl(string $url): BetterLocationCollection
	{
		$betterLocationCollection = new BetterLocationCollection();
		$parsedUrl = General::parseUrl($url);
		$messageInUrl = isset($parsedUrl['query']['msg']) ? htmlspecialchars($parsedUrl['query']['msg']) : null;

		if (preg_match('/--loc-[a-zA-Z0-9]+/', $url)) {
			$locationData = self::requestByLoc($url);
			// @TODO use property "name" or set of properties in "address.*" to better describe current location
			$location = new BetterLocation($url, $locationData->geo->latitude, $locationData->geo->longitude, self::class, self::TYPE_PLACE_ORIGINAL_ID);
			if ($messageInUrl) {
				$location->setPrefixMessage($location->getPrefixMessage() . ' ' . $messageInUrl);
			}
			$betterLocationCollection[] = $location;
		}

		if (preg_match('/^\/p\/s-[a-zA-Z0-9]+$/', $parsedUrl['path'])) { // from short links
			// need to replace from "share" subdomain, otherwise there would be another redirect
			$locationData = self::requestByLoc(str_replace('https://share.here.com/', 'https://wego.here.com/', $url));
			// @TODO use property "name" or set of properties in "address.*" to better describe current location
			$betterLocationCollection[] = new BetterLocation($url, $locationData->geo->latitude, $locationData->geo->longitude, self::class, self::TYPE_PLACE_SHARE);
		}

		if (isset($parsedUrl['path']) && preg_match(self::RE_COORDS_IN_MAP, $parsedUrl['path'], $matches)) {
			$location = new BetterLocation($url, floatval($matches[1]), floatval($matches[2]), self::class, self::TYPE_PLACE_COORDS);
			if ($messageInUrl) {
				$location->setPrefixMessage($location->getPrefixMessage() . ' ' . $messageInUrl);
			}
			$betterLocationCollection[] = $location;
		}
		if (isset($parsedUrl['query']['map']) && preg_match('/^(-?[0-9]{1,2}\.[0-9]{1,}),(-?[0-9]{1,3}\.[0-9]{1,}),/', $parsedUrl['query']['map'], $matches)) {
			$betterLocationCollection[] = new BetterLocation($url, floatval($matches[1]), floatval($matches[2]), self::class, self::TYPE_MAP);
		}
		if (count($betterLocationCollection) === 0) {
			Debugger::log(sprintf('From HereWeGo URL "%s" wasn\'t loaded any valid location.', $url), ILogger::WARNING);
		}
		return $betterLocationCollection;
	}

	/**
	 * @param string $url
	 * @return BetterLocationCollection
	 * @throws InvalidLocationException
	 */
	public static function parseCoordsMultiple(string $url): BetterLocationCollection
	{
		if (self::isShortUrl($url)) {
			$redirectUrl = MiniCurl::loadRedirectUrl($url);
			try {
				return self::processShortShareUrl($url, $redirectUrl);
			} catch (\Exception $exception) {
				Debugger::log(sprintf('Error while processing short URL "%s", fallback to parseUrl("%s"). Error: "%s"', $url, $redirectUrl, $exception->getMessage()), ILogger::WARNING);
				return self::parseUrl($redirectUrl);
			}
		} else if (self::isNormalUrl($url)) {
			return self::parseUrl($url);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords for Here WeGo maps link "%s".', $url));
		}
	}

	private static function requestByLoc($url): \stdClass
	{
		$response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_HERE_WE_GO_LOC)->run()->getBody();
		// @TODO probably could be solved somehow better. Needs more testing
		preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $response, $matches);
		return json_decode($matches[1]);
	}

	/**
	 * Process share URL which after two redirects contain map coordinates in URL which are the same as shared place coordinates.
	 * This allow skip doing actual request and downloading full page, just reading HTTP headers (much more resource friendly)
	 * Example (see test for more examples):
	 * -> https://her.is/3lZVXD3
	 * -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?ref=here_com
	 * -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?map=50.10957%2C14.44122%2C15%2Cnormal&ref=here_com
	 * = 50.10957, 14.44122
	 *
	 * @return BetterLocationCollection
	 * @throws InvalidLocationException
	 * @throws \Exception
	 */
	private static function processShortShareUrl(string $originalUrl, string $redirectUrl): BetterLocationCollection
	{
		$parsedNewLocation = General::parseUrl($redirectUrl);
		if ($parsedNewLocation['host'] !== 'share.here.com') {
			throw new \Exception(sprintf('Unexpected redirect URL "%s".', $parsedNewLocation['host']));
		}
		$redirectUrl2 = MiniCurl::loadRedirectUrl($redirectUrl);
		if ($redirectUrl2 === null) {
			throw new \Exception('Missing second redirect URL.');
		}
		$parsedNewLocation2 = General::parseUrl($redirectUrl2);
		if (isset($parsedNewLocation2['query']) === false) {
			throw new \Exception(sprintf('Missing "query" parameter in second redirect URL "%s".', $redirectUrl2));
		}
		if (isset($parsedNewLocation2['query']['map']) === false) {
			throw new \Exception(sprintf('Missing "map" parameter in query in second redirect URL "%s".', $redirectUrl2));
		}
		if (preg_match(self::RE_COORDS_IN_MAP, $parsedNewLocation2['query']['map'], $matches)) {
			$betterLocationCollection = new BetterLocationCollection();
			$betterLocationCollection[] = new BetterLocation($originalUrl, floatval($matches[1]), floatval($matches[2]), self::class, self::TYPE_PLACE_SHARE);
			return $betterLocationCollection;
		} else {
			throw new \Exception(sprintf('Missing map coordinates in second redirect URL "%s".', $redirectUrl2));
		}
	}
}
