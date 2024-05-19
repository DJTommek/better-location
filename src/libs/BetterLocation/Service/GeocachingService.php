<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Factory;
use App\Geocaching\Client;
use App\Geocaching\Types\GeocachePreviewType;
use App\Icons;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use App\Utils\StringUtils;
use Nette\Http\Url;
use Tracy\Debugger;
use Tracy\ILogger;

final class GeocachingService extends AbstractService
{
	const ID = 26;
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

	public function __construct(
		private readonly ?Client $geocachingClient = null,
	) {

	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_CACHE,
			self::TYPE_MAP_BROWSE,
			self::TYPE_MAP_SEARCH,
			self::TYPE_MAP_COORD,
		];
	}

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/play/map?lat=%1$F&lng=%2$F', $lat, $lon);
		}
	}

	/**
	 * @return array<string>
	 */
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
				$geocache = Factory::geocaching()->loadGeocachePreview($geocacheId);
				$collection->add(self::formatApiResponse($geocache, $geocacheId));
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::DEBUG);
				// do nothing, probably not valid cache
			}
		}
		return $collection;
	}

	public function validate(): bool
	{
		return $this->isUrl() || self::isGeocacheId($this->input);
	}

	private static function isGeocacheId(string $input): bool
	{
		return !!(preg_match('/' . self::CACHE_REGEX . '/', $input));
	}

	public function isUrl(): bool
	{
		return (
			$this->isUrlGeocache() ||
			$this->isUrlMapCoord() ||
			$this->isUrlMapBrowse() ||
			$this->isUrlCoordMap() ||
			$this->isUrlGuid()
		);
	}

	public static function getGeocacheIdFromUrl(Url $url): ?string
	{
		if (mb_strtolower($url->getDomain()) === 'geocaching.com') {
			if (preg_match(self::URL_PATH_GEOCACHE_REGEX, $url->getPath(), $matches)) {
				// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug
				// https://www.geocaching.com/geocache/GC3DYC4
				// https://www.geocaching.com/geocache/GC3DYC4_find-the-bug?guid=df11c170-1af3-4ee1-853a-e97c1afe0722
				return mb_strtoupper($matches[1]);
			} else if (preg_match(self::URL_PATH_MAP_GEOCACHE_REGEX, $url->getPath(), $matches)) {
				// https://www.geocaching.com/play/map/GC3DYC4
				return mb_strtoupper($matches[1]);
			} else if ($url->getPath() === '/seek/log.aspx' && preg_match('/^' . self::LOG_REGEX . '$/i', $url->getQueryParameter('code') ?? '', $matches)) {
				// https://www.geocaching.com/seek/log.aspx?code=GL133PQK0
				return null; // @TODO load log to get geocache ID (https://github.com/DJTommek/better-location/issues/35)
			} else if (
				mb_strpos($url->getPath(), '/play/map') === 0 && // might be "/play/map" or "/play/map/"
				preg_match('/^' . self::CACHE_REGEX . '$/i', $url->getQueryParameter('gc') ?? '')
			) {
				// https://www.geocaching.com/play/map?gc=GC3DYC4
				return mb_strtoupper($url->getQueryParameter('gc'));
			} else if ( // https://www.geocaching.com/seek/cache_details.aspx?wp=GC1GDKZ
				$url->getPath() === '/seek/cache_details.aspx' &&
				preg_match('/^' . self::CACHE_REGEX . '$/i', $url->getQueryParameter('wp') ?? '')
			) {
				return mb_strtoupper($url->getQueryParameter('wp'));
			}
		} else if (mb_strtolower($url->getDomain(2)) === 'coord.info' && preg_match('/^\/(' . self::CACHE_REGEX . ')$/i', $url->getPath(), $matches)) {
			return mb_strtoupper($matches[1]);
		}
		return null;
	}

	public function isUrlGeocache(): bool
	{
		if ($geocacheId = self::getGeocacheIdFromUrl($this->url)) {
			$this->data->geocacheId = $geocacheId;
			return true;
		} else {
			return false;
		}
	}

	public function isUrlGuid(): bool
	{
		if (
			$this->url->getDomain() === 'geocaching.com' &&
			$this->url->getPath() === '/seek/cache_details.aspx'
		) {
			// parameter GUID is case in-sensitive
			$parameters = array_change_key_case($this->url->getQueryParameters(), CASE_LOWER);
			if (StringUtils::isGuid($parameters['guid'] ?? '', false)) {
				$this->data->isUrlGuid = true;
				return true;
			}
		}
		return false;
	}

	/**
	 * Map type "Search geocaches"
	 *
	 * @see https://www.geocaching.com/play/map/
	 */
	public function isUrlMapCoord(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'geocaching.com' &&
			rtrim($this->url->getPath(), '/') === '/play/map' && // might be "/play/map" or "/play/map/"
			Coordinates::isLat($this->url->getQueryParameter('lat')) &&
			Coordinates::isLon($this->url->getQueryParameter('lng'))
		) {
			$this->data->mapCoord = true;
			$this->data->mapCoordLat = Strict::floatval($this->url->getQueryParameter('lat'));
			$this->data->mapCoordLon = Strict::floatval($this->url->getQueryParameter('lng'));
			return true;
		}
		return false;
	}

	/**
	 * Map type "Browse geocaches"
	 *
	 * @see https://www.geocaching.com/map/
	 */
	public function isUrlMapBrowse(): bool
	{
		if (
			$this->url->getDomain(2) === 'geocaching.com' &&
			$this->url->getPath() === '/map/' &&
			$this->url->getFragment()
		) {
			parse_str(ltrim($this->url->getFragment(), '?'), $fragmentQuery);
			if (isset($fragmentQuery['ll']) && preg_match('/^(-?[0-9.]+),(-?[0-9.]+)$/', $fragmentQuery['ll'], $matches)) {
				if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
					$this->data->mapBrowseCoord = true;
					$this->data->mapBrowseCoordLat = Strict::floatval($matches[1]);
					$this->data->mapBrowseCoordLon = Strict::floatval($matches[2]);
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Map type "Browse geocaches"
	 *
	 * @see https://www.geocaching.com/map/
	 */
	public function isUrlCoordMap(): bool
	{
		if (
			$this->url->getDomain(2) === 'coord.info' &&
			$this->url->getPath() === '/map' &&
			preg_match('/^(-?[0-9.]+),(-?[0-9.]+)$/', $this->url->getQueryParameter('ll') ?? '', $matches)
		) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				$this->data->coordCoord = true;
				$this->data->coordCoordLat = Strict::floatval($matches[1]);
				$this->data->coordCoordLon = Strict::floatval($matches[2]);
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		if ($this->geocachingClient === null) {
			throw new \RuntimeException('Geocaching API is not available.');
		}

		if ($this->data->isUrlGuid ?? false) {
			try {
				$this->url = Strict::url(MiniCurl::loadRedirectUrl($this->input));
				if ($this->validate() === false) {
					throw new InvalidLocationException(sprintf('Unprocessable input: "%s"', $this->input));
				}
			} catch (InvalidLocationException $exception) {
				throw $exception;
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				throw new InvalidLocationException(sprintf('Error while processing %s URL, try again later.', self::NAME));
			}
		}

		if ($this->data->geocacheId ?? null) {
			$geocache = $this->geocachingClient->loadGeocachePreview($this->data->geocacheId);
			$this->collection->add(self::formatApiResponse($geocache, $this->input));
		} else if ($this->data->mapCoord ?? false) {
			$this->collection->add(new BetterLocation($this->input, $this->data->mapCoordLat, $this->data->mapCoordLon, self::class, self::TYPE_MAP_SEARCH));
		} else if ($this->data->mapBrowseCoord ?? false) {
			$this->collection->add(new BetterLocation($this->input, $this->data->mapBrowseCoordLat, $this->data->mapBrowseCoordLon, self::class, self::TYPE_MAP_BROWSE));
		} else if ($this->data->coordCoord ?? false) {
			$this->collection->add(new BetterLocation($this->input, $this->data->coordCoordLat, $this->data->coordCoordLon, self::class, self::TYPE_MAP_COORD));
		} else {
			Debugger::log(sprintf('Unprocessable input: "%s"', $this->input), ILogger::ERROR);
		}
	}

	private static function formatApiResponse(GeocachePreviewType $geocache, string $input): BetterLocation
	{
		if ($geocache->premiumOnly === true) {
			throw new InvalidLocationException(sprintf('Cannot show coordinates for geocache <a href="%s">%s</a> - for Geocaching premium users only.', $geocache->getLink(), $geocache->code));
		}
		$betterLocation = new BetterLocation($input, $geocache->postedCoordinates->latitude, $geocache->postedCoordinates->longitude, self::class, self::TYPE_CACHE);
		$serviceName = preg_match('/^https?:\/\//', $input) ? sprintf('<a href="%s">%s</a>', $input, self::NAME) : self::NAME;
		$cacheCodeLink = sprintf('<a href="%s">%s</a>', $geocache->getLink(), $geocache->code);
		$cacheNameLink = sprintf('<a href="%s">%s</a>', $geocache->getLink(), trim($geocache->name));
		$textDisabled = $geocache->isDisabled() ? sprintf(' %s %s', Icons::WARNING, $geocache->getStatus()) : '';

		$betterLocation->setPrefixMessage(sprintf('%s %s%s', $serviceName, $cacheCodeLink, $textDisabled));
		$betterLocation->setInlinePrefixMessage(sprintf('%s %s: %s%s', $serviceName, $cacheCodeLink, $cacheNameLink, $textDisabled));
		$betterLocation->setDescription(sprintf('%s (%s, D: %s, T: %s)',
			trim($geocache->name),
			$geocache->getTypeAndSize(),
			sprintf($geocache->difficulty >= 4 ? '<b>%.1F</b>' : '%.1F', $geocache->difficulty),
			sprintf($geocache->terrain >= 4 ? '<b>%.1F</b>' : '%.1F', $geocache->terrain),
		));
		return $betterLocation;
	}
}
