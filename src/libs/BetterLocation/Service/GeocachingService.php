<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\Url;
use App\Factory;
use App\Geocaching\Client;
use App\Geocaching\Types\GeocachePreviewType;
use App\Icons;
use App\Utils\General;
use App\Utils\StringUtils;
use Tracy\Debugger;
use Tracy\ILogger;

final class GeocachingService extends AbstractService
{
	const NAME = 'Geocaching';

	const LINK = Client::LINK;
	const LINK_SHARE = Client::LINK_SHARE;

	const CACHE_REGEX = 'GC[A-Z0-9]{1,5}'; // keep limit as low as possible to best match and eliminate false positive
	const LOG_REGEX = 'GL[A-Z0-9]{1,7}'; // keep limit as low as possible to best match and eliminate false positive

	const CACHE_IN_TEXT_REGEX = '/(?:^|\W)(' . self::CACHE_REGEX . ')(?=(?:$|\W))/ims';

	/**
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
	 * https://www.geocaching.com/geocache/GC3DYC4
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
	 */
	const URL_PATH_GEOCACHE_REGEX = '/^\/geocache\/(' . self::CACHE_REGEX . ')($|_)/i'; // end or character "_"

	/**
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
	 * https://www.geocaching.com/geocache/GC3DYC4
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
	 */
	const URL_PATH_MAP_GEOCACHE_REGEX = '/^\/play\/map\/(' . self::CACHE_REGEX . ')$/i';

	const TYPE_CACHE = 'cache';
	const TYPE_MAP_BROWSE = 'browse map';
	const TYPE_MAP_SEARCH = 'search map';
	const TYPE_MAP_COORD = 'coord map';

	public static function getConstants(): array
	{
		return [
			self::TYPE_CACHE,
			self::TYPE_MAP_BROWSE,
			self::TYPE_MAP_SEARCH,
			self::TYPE_MAP_COORD,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/play/map?lat=%1$f&lng=%2$f', $lat, $lon);
		}
	}

	public static function getGeocachesIdFromText(string $text): array
	{
		$geocaches = [];
		$inStringRegex = self::CACHE_IN_TEXT_REGEX;
		if (preg_match_all($inStringRegex, $text, $matches)) {
			for ($i = 0; $i < count($matches[1]); $i++) {
				$geocaches[] = mb_strtoupper(trim($matches[1][$i]));
			}
		}
		return $geocaches;
	}

	public static function findInText(string $text): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		foreach (self::getGeocachesIdFromText($text) as $geocacheId) {
			try {
				$geocache = Factory::Geocaching()->loadGeocachePreview($geocacheId);
				$collection->add(self::formatApiResponse($geocache, $geocacheId));
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::DEBUG);
				// do nothing, probably not valid cache
			}
		}
		return $collection;
	}

	public static function isValid(string $input): bool
	{
		return self::isUrl($input) || self::isGeocacheId($input) || self::isLogId($input);
	}

	private static function isGeocacheId(string $input): bool
	{
		return !!(preg_match_all(self::CACHE_REGEX, $input));
	}

	private static function isLogId(string $input): bool
	{
		return false;  // @TODO currently disabled, waiting for https://github.com/DJTommek/better-location/issues/35
		return !!(preg_match_all(self::LOG_REGEX, $input));
	}

	public static function isUrl(string $url): bool
	{
		if (self::isCorrectDomainUrl($url)) {
			return (
				is_string(self::getCacheIdFromUrl($url)) ||
				is_array(self::getCoordsFromMapSearchUrl($url)) ||
				is_array(self::getCoordsFromMapBrowseUrl($url)) ||
				is_array(self::getCoordsFromMapCoordInfoUrl($url)) ||
				self::isGuidUrl($url)
			);
		}
		return false;
	}

	private static function isCorrectDomainUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['geocaching.com', 'www.geocaching.com', 'coord.info', 'www.coord.info'], true)
		);
	}

	private static function isGuidUrl($url): bool
	{
		$parsedUrl = General::parseUrl(mb_strtolower($url));
		return (
			in_array($parsedUrl['host'], ['geocaching.com', 'www.geocaching.com'], true) &&
			isset($parsedUrl['path']) &&
			$parsedUrl['path'] === '/seek/cache_details.aspx' &&
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['guid']) &&
			StringUtils::isGuid($parsedUrl['query']['guid'], false)
		);
	}

	public static function parseCoords(string $url): BetterLocation
	{
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($url, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from %s link %s.', self::NAME, $url));
		}
	}

	public static function getCacheIdFromUrl(string $url): ?string
	{
		$parsedUrl = General::parseUrl($url);
		if (isset($parsedUrl['path'])) {
			if (in_array(mb_strtolower($parsedUrl['host']), ['geocaching.com', 'www.geocaching.com'], true)) {
				if (preg_match(self::URL_PATH_GEOCACHE_REGEX, $parsedUrl['path'], $matches)) {
					// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
					// https://www.geocaching.com/geocache/GC3DYC4
					// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
					return mb_strtoupper($matches[1]);
				} else if (preg_match(self::URL_PATH_MAP_GEOCACHE_REGEX, $parsedUrl['path'], $matches)) {
					// https://www.geocaching.com/play/map/GC3DYC4
					return mb_strtoupper($matches[1]);
				} else if (isset($parsedUrl['query'])) {
					$query = $parsedUrl['query'];
					if (
						$parsedUrl['path'] === '/seek/log.aspx' &&
						isset($query['code']) &&
						preg_match('/^' . self::LOG_REGEX . '$/i', $query['code'], $matches)
					) {
						// https://www.geocaching.com/seek/log.aspx?code=GL133PQK0
						return null; // @TODO load log to get geocache ID
					} else if (
						mb_strpos($parsedUrl['path'], '/play/map') === 0 && // might be "/play/map" or "/play/map/"
						isset($query['gc']) &&
						preg_match('/^' . self::CACHE_REGEX . '$/i', $query['gc'], $matches)
					) {
						// https://www.geocaching.com/play/map?gc=GC3DYC4
						return mb_strtoupper($query['gc']);
					} else if ( // https://www.geocaching.com/seek/cache_details.aspx?wp=GC1GDKZ
						$parsedUrl['path'] === '/seek/cache_details.aspx' &&
						isset($query['wp']) &&
						preg_match('/^' . self::CACHE_REGEX . '$/i', $query['wp'], $matches)
					) {
						return mb_strtoupper($query['wp']);
					}
				}
			}
			if (in_array(mb_strtolower($parsedUrl['host']), ['coord.info', 'www.coord.info'])) {
				if (preg_match('/^\/(' . self::CACHE_REGEX . ')$/i', $parsedUrl['path'], $matches)) {
					return mb_strtoupper($matches[1]);
				}
			}
		}
		return null;
	}

	/**
	 * Map type "Search geocaches"
	 *
	 * @see https://www.geocaching.com/play/map/
	 */
	public static function getCoordsFromMapSearchUrl(string $url): ?array
	{
		$parsedUrl = General::parseUrl($url);
		if (
			isset($parsedUrl['path']) &&
			rtrim($parsedUrl['path'], '/') === '/play/map' && // might be "/play/map" or "/play/map/"
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['lat']) &&
			is_numeric($parsedUrl['query']['lat']) &&
			BetterLocation::isLatValid(floatval($parsedUrl['query']['lat'])) &&
			isset($parsedUrl['query']['lng']) &&
			is_numeric($parsedUrl['query']['lng']) &&
			BetterLocation::isLonValid(floatval($parsedUrl['query']['lng']))
		) {
			return [
				floatval($parsedUrl['query']['lat']),
				floatval($parsedUrl['query']['lng']),
			];
		} else {
			return null;
		}
	}

	/**
	 * Map type "Browse geocaches"
	 *
	 * @see https://www.geocaching.com/map/
	 */
	public static function getCoordsFromMapBrowseUrl(string $url): ?array
	{
		$parsedUrl = General::parseUrl($url);
		if (
			isset($parsedUrl['path']) &&
			$parsedUrl['path'] === '/map/' &&
			isset($parsedUrl['fragment'])
		) {
			parse_str(ltrim($parsedUrl['fragment'], '?'), $fragmentQuery);
			if (isset($fragmentQuery['ll']) && preg_match('/^(-?[0-9.]+),(-?[0-9.]+)$/', $fragmentQuery['ll'], $matches)) {
				if (BetterLocation::isLatValid(floatval($matches[1])) && BetterLocation::isLonValid(floatval($matches[2]))) {
					return [
						floatval($matches[1]),
						floatval($matches[2]),
					];
				}
			}
		}
		return null;
	}

	/**
	 * Short URL from coord.info obtainable on (and redirecting to) "Browse geocaches" map
	 *
	 * @see http://coord.info/map
	 */
	public static function getCoordsFromMapCoordInfoUrl(string $url): ?array
	{
		$parsedUrl = General::parseUrl($url);
		if (
			isset($parsedUrl['path']) &&
			$parsedUrl['path'] === '/map' &&
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['ll']) &&
			preg_match('/^(-?[0-9.]+),(-?[0-9.]+)$/', $parsedUrl['query']['ll'], $matches)
		) {
			if (BetterLocation::isLatValid(floatval($matches[1])) && BetterLocation::isLonValid(floatval($matches[2]))) {
				return [
					floatval($matches[1]),
					floatval($matches[2]),
				];
			}
		}
		return null;
	}

	public static function parseUrl(string $url): BetterLocation
	{
		$originalUrl = $url;
		if ($coords = self::getCoordsFromMapSearchUrl($url)) {
			return new BetterLocation($originalUrl, $coords[0], $coords[1], self::class, self::TYPE_MAP_SEARCH);
		} else if ($coords = self::getCoordsFromMapBrowseUrl($url)) {
			return new BetterLocation($originalUrl, $coords[0], $coords[1], self::class, self::TYPE_MAP_BROWSE);
		} else if ($coords = self::getCoordsFromMapCoordInfoUrl($url)) {
			return new BetterLocation($originalUrl, $coords[0], $coords[1], self::class, self::TYPE_MAP_COORD);
		} else {
			if (self::isGuidUrl($url)) {
				$url = Url::getRedirectUrl($url);
			}
			try {
				$geocacheId = self::getCacheIdFromUrl($url);
				$geocache = Factory::Geocaching()->loadGeocachePreview($geocacheId);
				return self::formatApiResponse($geocache, $originalUrl);
			} catch (InvalidLocationException $exception) {
				throw $exception;
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				throw new InvalidLocationException(sprintf('Error while processing %s URL, try again later.', self::NAME));
			}
		}
	}

	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}

	private static function formatApiResponse(GeocachePreviewType $geocache, string $input): BetterLocation
	{
		if ($geocache->premiumOnly === true) {
			throw new InvalidLocationException(sprintf('Cannot show coordinates for geocache <a href="%s">%s</a> - for Geocaching premium users only.', $geocache->getLink(), $geocache->code));
		}
		$betterLocation = new BetterLocation($input, $geocache->postedCoordinates->latitude, $geocache->postedCoordinates->longitude, self::class, self::TYPE_CACHE);
		$serviceName = preg_match('/^https?:\/\//', $input) ? sprintf('<a href="%s">%s</a>', $input, self::NAME) : self::NAME;
		$cacheCodeLink = sprintf('<a href="%s">%s</a>', $geocache->getLink(), $geocache->code);
		$cacheNameLink = sprintf('<a href="%s">%s</a>', $geocache->getLink(), $geocache->name);
		$textDisabled = $geocache->isDisabled() ? sprintf(' %s %s', Icons::WARNING, $geocache->getStatus()) : '';

		$betterLocation->setPrefixMessage(sprintf('%s %s%s', $serviceName, $cacheCodeLink, $textDisabled));
		$betterLocation->setInlinePrefixMessage(sprintf('%s %s: %s%s', $serviceName, $cacheCodeLink, $cacheNameLink, $textDisabled));
		$betterLocation->setDescription(sprintf('%s (%s, D: %s, T: %s)',
			$geocache->name,
			$geocache->getTypeAndSize(),
			sprintf($geocache->difficulty >= 4 ? '<b>%.1F</b>' : '%.1F', $geocache->difficulty),
			sprintf($geocache->terrain >= 4 ? '<b>%.1F</b>' : '%.1F', $geocache->terrain),
		));
		return $betterLocation;
	}
}
