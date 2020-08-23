<?php

declare(strict_types=1);

namespace BetterLocation;

use Utils\Coordinates;

class BetterLocationCollection implements \ArrayAccess, \Iterator, \Countable
{
	private $locations = [];
	private $errors = [];
	private $position = 0;

	public function __invoke() {
		return $this->locations;
	}

	public function add(BetterLocation $betterLocation) {
		$this->locations[] = $betterLocation;
	}

	public function getAll() {
		return array_merge($this->locations, $this->errors);
	}

	public function getLocations() {
		return $this->locations;
	}

	public function getErrors() {
		return $this->errors;
	}

	public function getFirst() {
		return reset($this->locations);
	}

	public function mergeCollection(BetterLocationCollection $betterLocationCollection): void {
		$this->locations = array_merge($this->locations, $betterLocationCollection->getAll());
	}

	public function filterTooClose(int $ignoreDistance = 0): void {
		$mostImportantLocation = $this->getFirst();
		foreach ($this->locations as $key => $googleMapsBetterLocation) {
			if ($mostImportantLocation === $googleMapsBetterLocation) {
				continue;
			} else {
				// @TODO possible optimalization to skip calculating distance: if 0, check if coordinates are same
				$distance = Coordinates::distance(
					$mostImportantLocation->getLat(),
					$mostImportantLocation->getLon(),
					$googleMapsBetterLocation->getLat(),
					$googleMapsBetterLocation->getLon(),
				);
				if ($distance < $ignoreDistance) {
					// Remove locations that are too close to main location
					unset($this->locations[$key]);
				} else {
					$googleMapsBetterLocation->setDescription(sprintf('%s Location is %d meters away from %s %s.', \Icons::WARNING, $distance, $mostImportantLocation->getName(), \Icons::ARROW_UP));
				}
			}
		}
	}

	public function offsetExists($offset) {
		return isset($this->locations[$offset]);
	}

	public function offsetGet($offset) {
		return isset($this->locations[$offset]) ? $this->locations[$offset] : null;
	}

	public function offsetSet($offset, $value) {
		if ($value instanceof BetterLocation) {
			if (is_null($offset)) {
				$this->locations[] = $value;
			} else {
				$this->locations[$offset] = $value;
			}
		} else if ($value instanceof \Exception) {
			if (is_null($offset)) {
				$this->errors[] = $value;
			} else {
				$this->errors[$offset] = $value;
			}
		} else {
			throw new \InvalidArgumentException('Accepting only BetterLocation or Exception objects.');
		}
	}

	public function offsetUnset($offset) {
		unset($this->locations[$offset]);
	}

	public function current() {
		return $this->locations[$this->position];
	}

	public function next() {
		++$this->position;
	}

	public function key() {
		return $this->position;
	}

	public function valid() {
		return isset($this->locations[$this->position]);
	}

	public function rewind() {
		$this->position = 0;
	}

	public function count() {
		return count($this->locations);
	}
}
