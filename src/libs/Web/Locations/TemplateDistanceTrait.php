<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\Web\Favorites\WebLocationDto;

trait TemplateDistanceTrait
{
	/**
	 * @return iterable<WebLocationDto>
	 */
	private abstract function getLocations(): iterable;

	/** @var array<array<float>> Calculated distances between all points */
	public array $distances = [];
	/** @TODO maybe INF constant should be used instead of PHP_FLOAT_MAX */
	public float $distanceSmallest = PHP_FLOAT_MAX;
	public float $distanceGreatest = 0;

	private function calculateDistances(): void
	{
		foreach ($this->getLocations() as $keyVertical => $locationVertical) {
			assert($locationVertical instanceof WebLocationDto);
			$this->distances[$keyVertical] = [];
			foreach ($this->getLocations() as $keyHorizontal => $locationHorizontal) {
				assert($locationHorizontal instanceof WebLocationDto);

				if ($keyVertical === $keyHorizontal) {
					$distance = null;
				} else {
					$distance = round($locationVertical->coords->distance($locationHorizontal->coords), 6);
					$this->distanceGreatest = max($distance, $this->distanceGreatest);
					$this->distanceSmallest = min($distance, $this->distanceSmallest);
				}
				$this->distances[$keyVertical][$keyHorizontal] = $distance;
			}
		}
	}
}

