<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\Icons;
use App\Utils\Coordinates;

class BetterLocationCollection implements \ArrayAccess, \Iterator, \Countable
{
	/** @var BetterLocation[] */
	private $locations = [];
	/** @var \Exception[] */
	private $errors = [];
	private $position = 0;

	public function __invoke()
	{
		return $this->locations;
	}

	public function add($betterLocation)
	{
		if ($betterLocation instanceof BetterLocation) {
			$this->locations[] = $betterLocation;
		} else if ($betterLocation instanceof \Throwable) {
			$this->errors[] = $betterLocation;
		} else {
			throw new \InvalidArgumentException(sprintf('%s is accepting only "%s" and "%s" objects.', self::class, BetterLocation::class, \Throwable::class));
		}
	}

	public function getAll()
	{
		return array_merge($this->locations, $this->errors);
	}

	/**
	 * @return BetterLocation[]
	 */
	public function getLocations(): array
	{
		return $this->locations;
	}

	public function getErrors()
	{
		return $this->errors;
	}

	public function getFirst()
	{
		return reset($this->locations);
	}

	public function mergeCollection(BetterLocationCollection $betterLocationCollection): void
	{
		foreach ($betterLocationCollection->getAll() as $betterLocation) {
			$this->add($betterLocation);
		}
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

	public function offsetExists($offset)
	{
		return isset($this->locations[$offset]);
	}

	public function offsetGet($offset)
	{
		return isset($this->locations[$offset]) ? $this->locations[$offset] : null;
	}

	public function offsetSet($offset, $value)
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

	public function offsetUnset($offset)
	{
		unset($this->locations[$offset]);
	}

	public function current()
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

	public function valid()
	{
		return isset($this->locations[$this->position]);
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function count()
	{
		return count($this->locations);
	}
}
