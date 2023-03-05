<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Factory;
use App\Icons;
use App\MiniCurl\Exceptions\TimeoutException;
use App\MiniCurl\MiniCurl;
use App\TelegramCustomWrapper\TelegramHelper;
use App\Utils\Coordinates;
use App\Utils\Formatter;
use App\Utils\Strict;
use App\Utils\StringUtils;
use App\Utils\Utils;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

class BetterLocationCollection implements \ArrayAccess, \Iterator, \Countable
{
	/** @var BetterLocation[] */
	private array $locations = [];
	private int $position = 0;
	public bool $filterTooClose = true;
	private ?UrlImmutable $staticMapUrl = null;

	public function __invoke(): array
	{
		return $this->locations;
	}

	/** Needs to be called when number or order of locations will change. */
	private function clearLazyLoad(): void
	{
		$this->staticMapUrl = null;
	}

	/** Add more location(s) into collection */
	public function add(BetterLocationCollection|BetterLocation $input): self
	{
		$this->offsetSet(null, $input);
		return $this;
	}

	/** @deprecated Use getLocations() instead */
	public function getAll(): array
	{
		return $this->locations;
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
		$this->clearLazyLoad();
	}

	/** @return BetterLocation[] */
	public function getLocations(): array
	{
		return $this->locations;
	}

	public function getFirst(): false|BetterLocation
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
				$distance = $mostImportantLocation->getCoordinates()->distance($location->getCoordinates());
				if ($distance < $ignoreDistance) {
					// Remove locations that are too close to main location
					unset($this->locations[$key]);
				} else {
					$location->setDescription(
						sprintf('%s Location is %s away from %s %s.',
							Icons::WARNING,
							htmlspecialchars(Formatter::distance($distance)),
							$mostImportantLocation->getSourceType() ?? 'previous location',
							Icons::ARROW_UP
						)
					);
				}
			}
		}
		$this->clearLazyLoad();
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
		$this->clearLazyLoad();
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
		} else if ($value instanceof BetterLocationCollection) {
			foreach ($value->getLocations() as $betterLocation) {
				$this->add($betterLocation);
			}
		} else if ($value instanceof \Throwable) {
			Debugger::log('Pushing exceptions to BetterLocationCollection is deprecated.', Debugger::WARNING);
			Debugger::log($value, Debugger::WARNING);
		} else {
			throw new \InvalidArgumentException(sprintf('%s is accepting only "%s" and "%s" objects.', self::class, BetterLocation::class, BetterLocationCollection::class));
		}
		$this->clearLazyLoad();
	}

	public function offsetUnset($offset): void
	{
		unset($this->locations[$offset]);
		$this->clearLazyLoad();
	}

	public function current(): BetterLocation
	{
		return $this->locations[$this->position];
	}

	public function next(): void
	{
		$this->position++;
	}

	public function key(): int
	{
		return $this->position;
	}

	public function valid(): bool
	{
		return isset($this->locations[$this->position]);
	}

	public function rewind(): void
	{
		$this->position = 0;
	}

	public function count(): int
	{
		return count($this->locations);
	}

	public function isEmpty(): bool
	{
		return $this->locations === [];
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
		$serviceManager = Factory::ServicesManager();

		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				$url = TelegramHelper::getEntityContent($message, $entity);

				if (Strict::isUrl($url) === false) {
					continue;
				}

				$url = self::handleShortUrl($url);

				$serviceCollection = $serviceManager->iterate($url);
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
		$betterLocationsCollection->add($serviceManager->iterateText($messageWithoutUrls));
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
			if ($headers && isset($headers['content-type']) && Utils::checkIfValueInHeaderMatchArray($headers['content-type'], Url::CONTENT_TYPE_IMAGE_EXIF)) {
				$betterLocationExif = BetterLocation::fromExif($url);
				if ($betterLocationExif instanceof BetterLocation) {
					$betterLocationExif->setPrefixMessage(sprintf('<a href="%s">EXIF</a>', $url));
					$collection->add($betterLocationExif);
				}
			}
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
		return $collection;
	}

	public function getStaticMapUrl(): ?UrlImmutable
	{
		if (is_null($this->staticMapUrl)) {
			$staticMapProxy = StaticMapProxy::fromLocations($this);
			if ($staticMapProxy !== null) {
				$this->staticMapUrl = $staticMapProxy->publicUrl();
			}
		}
		return $this->staticMapUrl;
	}

	public function getKeys(): array
	{
		return array_map(function (BetterLocation $location) {
			return $location->key();
		}, $this->getLocations());
	}

	/** @return Coordinates[] */
	public function getCoordinates(): array
	{
		return array_map(function (BetterLocation $location) {
			return $location->getCoordinates();
		}, $this->getLocations());
	}

	/**
	 * Load elevations from API and fill it into all locations
	 */
	public function fillElevations(): void
	{
		try {
			$api = Factory::OpenElevation();
			$api->fillBatch($this->getCoordinates());
		} catch (TimeoutException) {
			Debugger::log('Unable to batch-fill coordinates elevation, request timeouted.', Debugger::WARNING);
		} catch (\Exception $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	/** Load addresses from API for all locations in this collection */
	public function fillAddresses(): void
	{
		foreach ($this->getLocations() as $location) {
			$location->generateAddress();
		}
	}

	/** Load datetime zone info for all locations in this collection */
	public function fillDatetimeZone(): void
	{
		foreach ($this->getLocations() as $location) {
			$location->generateDateTimeZone();
		}
	}

	public function getLink(string $format = null): UrlImmutable
	{
		$keys = $this->getKeys();
		if (count($keys) === 0) {
			throw new \Exception(sprintf('Unable to generate getFileLink(%s): Collection is empty.', $format ?? ''));
		}
		$result = Config::getAppUrl('/' . implode(';', $keys));
		return $result->withQueryParameter('format', $format);
	}
}
