<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use Utils\General;
use Icons;

abstract class AbstractService
{
	/**
	 * @param $url
	 * @return mixed|null
	 * @throws \Exception
	 */
	protected function getLocationFromHeaders($url) {
		$headers = General::getHeaders($url);
		return $headers['Location'] ?? null;
	}

	abstract public function run();

	public function generateBetterLocation(float $lat, float $lon) {
		$links = [];
		// Google maps
		$googleLink = sprintf(GoogleMapsService::LINK, $lat, $lon);
		$links[] = sprintf('<a href="%s">Google</a>', $googleLink);
		// Mapy.cz
		$mapyCzLink = sprintf('https://en.mapy.cz/zakladni?y=%1$f&x=%2$f&source=coor&id=%2$f%%2C%1$f', $lat, $lon);
		$links[] = sprintf('<a href="%s">Mapy.cz</a>', $mapyCzLink);
		// Waze
		$wazeLink = sprintf('https://www.waze.com/ul?ll=%1$f,%2$f&navigate=yes', $lat, $lon);
		$links[] = sprintf('<a href="%s">Waze</a>', $wazeLink);
		// OpenStreetMap
		$openStreetMapLink = sprintf('https://www.openstreetmap.org/search?whereami=1&query=%1$f,%2$f&mlat=%1$f&mlon=%2$f#map=17/%1$f/%2$f', $lat, $lon);
		$links[] = sprintf('<a href="%s">OSM</a>', $openStreetMapLink);
		// Intel
		$intelLink = sprintf('https://intel.ingress.com/intel?ll=%1$f,%2$f&pll=%1$f,%2$f', $lat, $lon);
		$links[] = sprintf('<a href="%s">Intel</a>', $intelLink);

		return sprintf('%s <code>%f,%f</code>:%s%s', Icons::SUCCESS, $lat, $lon, PHP_EOL, join(' | ', $links)) . PHP_EOL . PHP_EOL;
	}

}
