<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Config;
use App\Factory;
use App\Icons;
use App\Utils\Coordinates;
use App\Utils\Formatter;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use unreal4u\TelegramAPI\Telegram\Types\MessageEntity;

/**
 * @implements \ArrayAccess<int,BetterLocation>
 * @implements \Iterator<int,BetterLocation>
 */
class BetterLocationCollection implements \ArrayAccess, \Iterator, \Countable
{
	/** @var list<BetterLocation> */
	private array $locations = [];
	private int $position = 0;
	public bool $filterTooClose = true;
	private ?UrlImmutable $staticMapUrl = null;

	/**
	 * Set to true or false to override checking individual locations in this collection. Useful if collection
	 * is empty.
	 */
	public ?bool $hasRefreshableLocation = null;

	/**
	 * @return BetterLocation[]
	 */
	public function __invoke(): array
	{
		return $this->locations;
	}

	/** Needs to be called when number or order of locations will change. */
	private function clearLazyLoad(): void
	{
		// Recalculate keys to have list <0,inf>
		$this->locations = array_values($this->locations);
		$this->staticMapUrl = null;
	}

	/** Add more location(s) into collection */
	public function add(BetterLocationCollection|BetterLocation $input): self
	{
		$this->offsetSet(null, $input);
		return $this;
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
							Icons::ARROW_UP,
						),
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
		try {
			if ($value instanceof BetterLocation) {
				if (is_null($offset)) {
					$this->locations[] = $value;
				} else {
					$this->locations[$offset] = $value;
				}
				return;
			}

			if ($value instanceof BetterLocationCollection) {
				foreach ($value->getLocations() as $betterLocation) {
					$this->add($betterLocation);
				}
				if ($value->hasRefreshableLocation !== null) {
					$this->hasRefreshableLocation = $value->hasRefreshableLocation;
				}
				return;
			}

			if ($value instanceof \Throwable) {
				Debugger::log('Pushing exceptions to BetterLocationCollection is deprecated.', Debugger::WARNING);
				Debugger::log($value, Debugger::WARNING);
				return;
			}

		} finally {
			$this->clearLazyLoad();
		}

		throw new \InvalidArgumentException(sprintf('%s is accepting only "%s" and "%s" objects.', self::class, BetterLocation::class, BetterLocationCollection::class));
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
		if ($this->hasRefreshableLocation !== null) {
			return $this->hasRefreshableLocation;
		}

		foreach ($this->locations as $location) {
			if ($location->isRefreshable()) {
				return true;
			}
		}
		return false;
	}

	/**
	 * @param MessageEntity[] $entities
	 * @deprecated use \App\BetterLocation\FromTelegramMessage::getCollection() instead
	 */
	public static function fromTelegramMessage(string $message, array $entities): self
	{
		$fromTelegramMessage = new FromTelegramMessage(
			Factory::servicesManager(),
			Factory::requestor(),
			Factory::httpClient(),
		);
		return $fromTelegramMessage->getCollection($message, $entities);
	}

	public function getStaticMapUrl(): ?UrlImmutable
	{
		if (is_null($this->staticMapUrl)) {
			$staticMapProxyFactory = Factory::staticMapProxyFactory();
			$staticMapProxy = $staticMapProxyFactory->fromLocations($this);
			if ($staticMapProxy !== null) {
				$this->staticMapUrl = $staticMapProxy->publicUrl();
			}
		}
		return $this->staticMapUrl;
	}

	/**
	 * @return array<string>
	 */
	public function getKeys(): array
	{
		return array_map(function (BetterLocation $location) {
			return $location->getLatLon();
		}, $this->getLocations());
	}

	/** @return Coordinates[] */
	public function getCoordinates(): array
	{
		return array_map(function (BetterLocation $location) {
			return $location->getCoordinates();
		}, $this->getLocations());
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
