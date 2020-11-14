<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Factory;
use App\Geocaching\Client;
use App\Utils\General;
use Tracy\Debugger;
use Tracy\ILogger;

final class GeocachingService extends AbstractService
{
	const NAME = 'Geocaching';

	const LINK = Client::LINK;

	const CACHE_REGEX = 'GC[a-zA-Z0-9]{1,5}'; // keep limit as low as possible to best match and eliminate false positive
	const LOG_REGEX = 'GL[a-zA-Z0-9]{1,7}'; // keep limit as low as possible to best match and eliminate false positive

	/**
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
	 * https://www.geocaching.com/geocache/GC3DYC4
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
	 */
	const URL_PATH_GEOCACHE_REGEX = '/^\/geocache\/(' . self::CACHE_REGEX . ')($|_)/'; // end or character "_"

	/**
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
	 * https://www.geocaching.com/geocache/GC3DYC4
	 * https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
	 */
	const URL_PATH_MAP_GEOCACHE_REGEX = '/^\/play\/map\/(' . self::CACHE_REGEX . ')$/';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/play/map?lat=%1$f&lng=%2$f&', $lat, $lon);
		}
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
		return !!(preg_match_all(self::LOG_REGEX, $input));
	}

	public static function isUrl(string $url): bool
	{
		if (self::isCorrectDomainUrl($url)) {
			return is_string(self::getCacheIdFromUrl($url));
		}
		return false;
	}

	private static function isCorrectDomainUrl($url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['geocaching.com', 'www.geocaching.com'])
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
			if (preg_match(self::URL_PATH_GEOCACHE_REGEX, $parsedUrl['path'], $matches)) {
				// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
				// https://www.geocaching.com/geocache/GC3DYC4
				// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
				return $matches[1];
			} else if (preg_match(self::URL_PATH_MAP_GEOCACHE_REGEX, $parsedUrl['path'])) {
				// https://www.geocaching.com/play/map/GC3DYC4
				return $matches[1];
			} else if (isset($parsedUrl['query'])) {
				$query = $parsedUrl['query'];
				if (isset($query['code']) && preg_match('/^' . self::CACHE_REGEX . '$/', $query['code'], $matches)) {
					// https://www.geocaching.com/seek/log.aspx?code=GL133PQK0
					return $query['code'];
				} else if (isset($query['gc']) && preg_match('/^' . self::CACHE_REGEX . '$/', $query['gc'], $matches)) {
					// https://www.geocaching.com/play/map?gc=GC3DYC4
					return $query['gc'];
				}
			}
		}
		return null;
	}

	public static function parseUrl(string $url): BetterLocation
	{
		try {
			$geocacheId = self::getCacheIdFromUrl($url);
			$geocache = Factory::Geocaching()->loadGeocachePreview($geocacheId);
			$betterLocation = new BetterLocation($url, $geocache->postedCoordinates->latitude, $geocache->postedCoordinates->longitude, self::class);
			$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>',
				$url,
				self::NAME,
				$geocache->getLink(),
				$geocache->code,
			));
			$betterLocation->setDescription(sprintf('<b>%s</b> (%s, %s, difficulty: %.1F, terrain: %.1F)',
				htmlentities($geocache->name),
				$geocache->getType(),
				$geocache->getSize(),
				$geocache->terrain,
				$geocache->difficulty,
			));
			return $betterLocation;
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::EXCEPTION);
			throw new InvalidLocationException(sprintf('Error while processing %s URL, try again later.', self::NAME));
		}
	}

	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
