<?php

declare(strict_types=1);

namespace BetterLocation;

use \BetterLocation\Service\Exceptions\BadWordsException;
use \BetterLocation\Service\GoogleMapsService;
use \BetterLocation\Service\IngressIntelService;
use \BetterLocation\Service\MapyCzService;
use \BetterLocation\Service\OpenStreetMapService;
use \BetterLocation\Service\OpenLocationCodeService;
use \BetterLocation\Service\WazeService;
use \BetterLocation\Service\WhatThreeWordService;
use \Utils\Coordinates;
use \Utils\General;
use \Icons;

class BetterLocation
{
	private $lat;
	private $lon;
	private $prefixMessage;

	public function __construct(float $lat, float $lon, string $prefixMessage) {
		$this->lat = $lat;
		$this->lon = $lon;
		$this->prefixMessage = $prefixMessage;
	}

	/**
	 * @param string $text
	 * @param array $entities
	 * @return BetterLocation[]
	 * @throws \Exception
	 */
	public static function generateFromTelegramMessage(string $text, array $entities): array {
		$betterLocationsObjects = [];
		// @TODO remove this ugly dummy...
		$dummyBetterLocation = new BetterLocation(49.0, 15.0, '');

		$index = 0;
		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				if ($entity->type === 'url') { // raw url
					$url = mb_substr($text, $entity->offset, $entity->length);
				} else if ($entity->type === 'text_link') { // url hidden in text
					$url = $entity->url;
				} else {
					throw new \Exception('Unhandled entity type');
				}

				if (GoogleMapsService::isValid($url)) {
					$betterLocationsObjects[] = GoogleMapsService::parseCoords($url);
				} else if (MapyCzService::isValid($url)) {
					$betterLocationsObjects[] = MapyCzService::parseCoords($url);
				} else if (OpenStreetMapService::isValid($url)) {
					$betterLocationsObjects[] = OpenStreetMapService::parseCoords($url);
				} else if (OpenLocationCodeService::isValid($url)) {
					$betterLocationsObjects[] = OpenLocationCodeService::parseCoords($url);
				} else if (WazeService::isValid($url)) {
					$betterLocationsObjects[] = WazeService::parseCoords($url);
				} else if (WhatThreeWordService::isValid($url)) {
					$betterLocationsObjects[] = WhatThreeWordService::parseCoords($url);
				} else if (IngressIntelService::isValid($url)) {
					$betterLocationsObjects[] = IngressIntelService::parseCoords($url);
				}
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES, $dummyBetterLocation->getTextWithoutUrls($text, $entities), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$betterLocationsObjects[] = new BetterLocation(
					floatval($matches[1][$i]),
					floatval($matches[2][$i]),
					sprintf('#%d (Coords): ', ++$index),
				);
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES, $dummyBetterLocation->getTextWithoutUrls($text, $entities), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$betterLocationsObjects[] = new BetterLocation(
					Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[2][$i]),
					Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), $matches[6][$i]),
					sprintf('#%d (Coords): ', ++$index),
				);
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES_SECONDS, $dummyBetterLocation->getTextWithoutUrls($text, $entities), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$betterLocationsObjects[] = new BetterLocation(
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[2][$i]), floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[5][$i]),
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), floatval($matches[9][$i]), $matches[10][$i]),
					sprintf('#%d (Coords): ', ++$index),
				);
			}
		}

		foreach (preg_split('/[^\p{L}+]+/u', $dummyBetterLocation->getTextWithoutUrls($text, $entities)) as $word) {
			if (OpenLocationCodeService::isValid($word)) {
				$betterLocationsObjects[] = OpenLocationCodeService::parseCoords($word);
			}
		}

		if (preg_match_all(WhatThreeWordService::RE_IN_STRING, $dummyBetterLocation->getTextWithoutUrls($text, $entities), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$words = $matches[0][$i];
				if (WhatThreeWordService::isWords($words)) {
					try {
						$betterLocationsObjects[] = WhatThreeWordService::parseCoords($words);
					} catch (\Exception $exception) {
						/** @noinspection PhpStatementHasEmptyBodyInspection */
						if ($exception instanceof BadWordsException) {
							// pass
						} else {
							throw $exception;
						}
					}
				}
			}

		}

		return $betterLocationsObjects;
	}

	private function getTextWithoutUrls(string $text, array $entities) {
		foreach (array_reverse($entities) as $entity) {
			if ($entity->type === 'url') {
				$text = General::substrReplace($text, '<a>', $entity->offset, $entity->length);
			}
		}
		return $text;
	}

	public function generateBetterLocation() {
		$links = [];
		// Google maps
		$links[] = sprintf('<a href="%s">Google</a>', GoogleMapsService::getLink($this->lat, $this->lon));
		// Mapy.cz
		$links[] = sprintf('<a href="%s">Mapy.cz</a>', MapyCzService::getLink($this->lat, $this->lon));
		// Waze
		$links[] = sprintf('<a href="%s">Waze</a>', WazeService::getLink($this->lat, $this->lon, true));
		// OpenStreetMap
		$links[] = sprintf('<a href="%s">OSM</a>', OpenStreetMapService::getLink($this->lat, $this->lon));
		// Intel
		$links[] = sprintf('<a href="%s">Intel</a>', IngressIntelService::getLink($this->lat, $this->lon));

		return sprintf('%s %s <code>%f,%f</code>:%s%s', $this->prefixMessage, Icons::SUCCESS, $this->lat, $this->lon, PHP_EOL, join(' | ', $links)) . PHP_EOL . PHP_EOL;
	}
}
