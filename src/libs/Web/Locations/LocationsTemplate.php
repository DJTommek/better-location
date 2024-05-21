<?php declare(strict_types=1);

namespace App\Web\Locations;

use App\BetterLocation\BetterLocationCollection;
use App\Utils\DateImmutableUtils;
use App\Web\Favorites\JsLocationDto;
use App\Web\Favorites\WebLocationDto;
use App\Web\LayoutTemplate;

class LocationsTemplate extends LayoutTemplate
{
	use TemplateDistanceTrait;

	/** @var array<string> */
	public array $locationsKeys;

	/** @var array<WebLocationDto> */
	public array $locations;

	/** @var array<string,array<array{name: string, share?: string, drive?: string, text?: string, static?: string}>> */
	public array $websites = [];

	/** @var array<array{float, float}> */
	public array $allCoords = [];

	/** @var array<JsLocationDto> */
	public array $locationsJs = [];

	/** @var string Text representation of now in UTC */
	public string $nowUtcText;

	public bool $showingTimezoneData = false;
	public bool $showingElevation = false;

	/**
	 * @param array<string,array<array{name: string, share?: string, drive?: string, text?: string, static?: string}>> $websites
	 */
	public function prepare(BetterLocationCollection $collection, array $websites): void
	{
		foreach ($collection as $location) {
			$coordsText = $location->getLatLon();
			$this->locations[] = new WebLocationDto($location, $coordsText, $coordsText);

			$this->locationsKeys[] = $coordsText;

			$this->locationsJs[] = new JsLocationDto($location);

			$this->allCoords[] = [$location->getLat(), $location->getLon()];
		}

		$this->websites = $websites;
		$this->nowUtcText = DateImmutableUtils::nowUtc()->format(DATE_ISO8601);
		$this->calculateDistances();
	}

	/**
	 * @return iterable<WebLocationDto>
	 */
	private function getLocations(): iterable
	{
		return $this->locations;
	}
}

