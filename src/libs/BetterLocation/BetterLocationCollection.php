<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\Coordinates\MGRSService;
use App\BetterLocation\Service\Coordinates\USNGService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesSecondsService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesMinutesService;
use App\BetterLocation\Service\Coordinates\WGS84DegreesService;
use App\BetterLocation\Service\GeocachingService;
use App\BetterLocation\Service\GeohashService;
use App\BetterLocation\Service\OpenLocationCodeService;
use App\BetterLocation\Service\WhatThreeWordService;
use App\Config;
use App\Factory;
use App\Icons;
use App\MiniCurl\MiniCurl;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\General;
use App\Utils\Strict;
use App\Utils\StringUtils;
use App\WhatThreeWord\Helper;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class BetterLocationCollection implements \ArrayAccess, \Iterator, \Countable
{
	/** @var BetterLocation[] */
	private $locations = [];
	/** @var \Throwable[] */
	private $errors = [];
	private $position = 0;
	/** @var bool */
	public $filterTooClose = true;

	public function __invoke(): array
	{
		return $this->locations;
	}

	/** @param BetterLocation|BetterLocationCollection|\Throwable $input */
	public function add($input): self
	{
		if ($input instanceof BetterLocation) {
			$this->locations[] = $input;
		} else if ($input instanceof \Throwable) {
			$this->errors[] = $input;
		} else if ($input instanceof BetterLocationCollection) {
			foreach ($input->getAll() as $betterLocation) {
				$this->add($betterLocation);
			}
		} else {
			throw new \InvalidArgumentException(sprintf('%s is accepting only "%s", "%s" and "%s" objects.', self::class, BetterLocation::class, BetterLocationCollection::class, \Throwable::class));
		}
		return $this;
	}

	public function getAll()
	{
		return array_merge($this->locations, $this->errors);
	}

	public function getByLatLon(float $lat, float $lon): ?BetterLocation
	{
		$key = sprintf('%F,%F', $lat, $lon);
		foreach ($this->getLocations() as $location) {
			if ($location->__toString() === $key) {
				return $location;
			}
		}
		return null;
	}

	public function removeByLatLon(float $lat, float $lon): void
	{
		$key = sprintf('%F,%F', $lat, $lon);
		foreach ($this->locations as $location) {
			if ($location->__toString() === $key) {
				unset($this->locations[$key]);
				return;
			}
		}
	}

	/**
	 * @return BetterLocation[]
	 */
	public function getLocations(): array
	{
		return $this->locations;
	}

	public function getErrors(): array
	{
		return $this->errors;
	}

	/** @return BetterLocation|false */
	public function getFirst()
	{
		return reset($this->locations);
	}

	public function filterTooClose(int $ignoreDistance = 0): void
	{
		$mostImportantLocation = $this->getFirst();
		foreach ($this->locations as $key => $location) {
			if ($mostImportantLocation === $location) {
				continue;
			} else {
				// @TODO possible optimalization to skip calculating distance: if 0, check if coordinates are same
				$distance = Coordinates::distance(
					$mostImportantLocation->getLat(),
					$mostImportantLocation->getLon(),
					$location->getLat(),
					$location->getLon(),
				);
				if ($distance < $ignoreDistance) {
					// Remove locations that are too close to main location
					unset($this->locations[$key]);
				} else {
					$location->setDescription(sprintf('%s Location is %d meters away from %s %s.', Icons::WARNING, $distance, $mostImportantLocation->getName(), Icons::ARROW_UP));
				}
			}
		}
	}

	/**
	 * Remove locations with exact same coordinates and keep only one
	 */
	public function deduplicate(): void
	{
		// @TODO fix deduplicate if refreshable location
		// If is send location to some place and then refreshable location to the same place,
		// second location is removed, thus refreshable button is not available

		$originalCoordinates = [];
		foreach ($this->locations as $location) {
			$key = $location->__toString();
			if (isset($originalCoordinates[$key])) {
				$originalCoordinates[$key]++;
			} else {
				$originalCoordinates[$key] = 1;
			}
		}

		$coordinates = $originalCoordinates; // copy array
		// array_reverse to remove all other duplicated locations but first
		foreach (array_reverse($this->locations, true) as $collectionKey => $location) {
			$key = $location->__toString();
			if ($coordinates[$key] > 1) {
				unset($this->locations[$collectionKey]);
				$coordinates[$key]--;
			} else if ($coordinates[$key] === 1 && $originalCoordinates[$key] > 1) { // add info that coordinates was deduplicated
				$this->locations[$collectionKey]->setCoordinateSuffixMessage(sprintf('(%dx)', $originalCoordinates[$key]));
			}
		}
	}

	public function offsetExists($offset): bool
	{
		return isset($this->locations[$offset]);
	}

	public function offsetGet($offset): ?BetterLocation
	{
		return $this->locations[$offset] ?? null;
	}

	public function offsetSet($offset, $value): void
	{
		if ($value instanceof BetterLocation) {
			if (is_null($offset)) {
				$this->locations[] = $value;
			} else {
				$this->locations[$offset] = $value;
			}
		} else if ($value instanceof \Throwable) {
			if (is_null($offset)) {
				$this->errors[] = $value;
			} else {
				$this->errors[$offset] = $value;
			}
		} else {
			throw new \InvalidArgumentException('Accepting only BetterLocation or Exception objects.');
		}
	}

	public function offsetUnset($offset): void
	{
		unset($this->locations[$offset]);
	}

	public function current(): BetterLocation
	{
		return $this->locations[$this->position];
	}

	public function next()
	{
		++$this->position;
	}

	public function key()
	{
		return $this->position;
	}

	public function valid(): bool
	{
		return isset($this->locations[$this->position]);
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function count(): int
	{
		return count($this->locations) + count($this->errors);
	}

	public function hasRefreshableLocation(): bool
	{
		foreach ($this->locations as $location) {
			if ($location->isRefreshable()) {
				return true;
			}
		}
		return false;
	}

	/** @param MessageEntity[] $entities */
	public static function fromTelegramMessage(string $message, array $entities): self
	{
		$betterLocationsCollection = new self();

		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				$url = TelegramHelper::getEntityContent($message, $entity);

				if (Strict::isUrl($url) === false) {
					continue;
				}

				$url = self::handleShortUrl($url);

				$serviceCollection = Factory::ServicesManager()->iterate($url);
				if ($serviceCollection->filterTooClose) {
					$serviceCollection->filterTooClose(Config::DISTANCE_IGNORE);
				}
				$betterLocationsCollection->add($serviceCollection);

				if (count($serviceCollection) === 0) { // process HTTP headers only if no location was found via iteration
					$betterLocationsCollection->add(self::processHttpHeaders($url));
				}
			}
		}

		$messageWithoutUrls = TelegramHelper::getMessageWithoutUrls($message, $entities);
		$messageWithoutUrls = StringUtils::translit($messageWithoutUrls);

		$betterLocationsCollection->add(WGS84DegreesService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(WGS84DegreesMinutesService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(WGS84DegreesMinutesSecondsService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(MGRSService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(USNGService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(OpenLocationCodeService::findInText($messageWithoutUrls));
		$betterLocationsCollection->add(GeohashService::findInText($messageWithoutUrls));
		if (is_null(Config::GEOCACHING_COOKIE) === false) {
			$betterLocationsCollection->add(GeocachingService::findInText($messageWithoutUrls));
		}

		// What Three Word
		if (is_null(Config::W3W_API_KEY) === false && $wordsAddresses = Helper::findInText($messageWithoutUrls)) {
			foreach ($wordsAddresses as $wordsAddress) {
				// It is ok to use processStatic since words should be already valid
				$betterLocationsCollection->add(WhatThreeWordService::processStatic($wordsAddress)->getCollection());
			}
		}

		$betterLocationsCollection->deduplicate();

		return $betterLocationsCollection;
	}

	private static function handleShortUrl(string $url): string
	{
		$originalUrl = $url;
		$tries = 0;
		while (is_null($url) === false && Url::isShortUrl($url)) {
			if ($tries >= 5) {
				Debugger::log(sprintf('Too many tries (%d) for translating original URL "%s"', $tries, $originalUrl));
			}
			$url = MiniCurl::loadRedirectUrl($url);
			$tries++;
		}
		if (is_null($url)) { // in case of some error, revert to original URL
			$url = $originalUrl;
		}
		return $url;
	}

	private static function processHttpHeaders($url): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		try {
			$headers = null;
			try {
				$headers = MiniCurl::loadHeaders($url);
			} catch (\Throwable $exception) {
				Debugger::log(sprintf('Error while loading headers for URL "%s": %s', $url, $exception->getMessage()));
			}
			if ($headers && isset($headers['content-type']) && General::checkIfValueInHeaderMatchArray($headers['content-type'], Url::CONTENT_TYPE_IMAGE_EXIF)) {
				$betterLocationExif = BetterLocation::fromExif($url);
				if ($betterLocationExif instanceof BetterLocation) {
					$betterLocationExif->setPrefixMessage(sprintf('<a href="%s">EXIF</a>', $url));
					$collection->add($betterLocationExif);
				}
			}
		} catch (\Exception $exception) {
			$collection->add($exception);
		}
		return $collection;
	}

	public function getStaticMapUrl(): string
	{
		$staticMap = Factory::StaticMapProxy();
		$staticMap->addMarkers($this)->downloadAndCache();
		return $staticMap->getUrl();
	}
}
