<?php

declare(strict_types=1);

namespace BetterLocation;

use BetterLocation\Service\AbstractService;
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
	 * @param string $message
	 * @param array $entities
	 * @return BetterLocation[] | \InvalidArgumentException[]
	 * @throws \Exception
	 */
	public static function generateFromTelegramMessage(string $message, array $entities): array {
		$betterLocationsObjects = [];

		$index = 0;
		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				if ($entity->type === 'url') { // raw url
					$url = mb_substr($message, $entity->offset, $entity->length);
				} else if ($entity->type === 'text_link') { // url hidden in text
					$url = $entity->url;
				} else {
					throw new \InvalidArgumentException('Unhandled Telegram entity type');
				}

				try {
					if (GoogleMapsService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = GoogleMapsService::parseCoords($url);
					} else if (MapyCzService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = MapyCzService::parseCoords($url);
					} else if (OpenStreetMapService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = OpenStreetMapService::parseCoords($url);
					} else if (OpenLocationCodeService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = OpenLocationCodeService::parseCoords($url);
					} else if (WazeService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = WazeService::parseCoords($url);
					} else if (WhatThreeWordService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = WhatThreeWordService::parseCoords($url);
					} else if (IngressIntelService::isValid($url)) {
						$betterLocationsObjects[$entity->offset] = IngressIntelService::parseCoords($url);
					}
				} catch (\Exception $exception) {
					$betterLocationsObjects[$entity->offset] = $exception;
				}
			}
		}

		$messageWithoutUrls = self::getMessageWithoutUrls($message, $entities);

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES, $messageWithoutUrls, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				try {
					$betterLocationsObjects[] = new BetterLocation(
						floatval($matches[1][$i]),
						floatval($matches[2][$i]),
						sprintf('#%d (Coords): ', ++$index),
					);
				} catch (\Exception $exception) {
					$betterLocationsObjects[] = $exception;
				}
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES, $messageWithoutUrls, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				try {
					$betterLocationsObjects[] = new BetterLocation(
						Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[2][$i]),
						Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), $matches[6][$i]),
						sprintf('#%d (Coords): ', ++$index),
					);
				} catch (\Exception $exception) {
					$betterLocationsObjects[] = $exception;
				}
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES_SECONDS, $messageWithoutUrls, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				try {
					$betterLocationsObjects[] = new BetterLocation(
						Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[2][$i]), floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[5][$i]),
						Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), floatval($matches[9][$i]), $matches[10][$i]),
						sprintf('#%d (Coords): ', ++$index),
					);
				} catch (\Exception $exception) {
					$betterLocationsObjects[] = $exception;
				}
			}
		}

		// OpenLocationCode (Plus codes)
		foreach (preg_split('/[^\p{L}+]+/u', $messageWithoutUrls) as $word) {
			try {
				if (OpenLocationCodeService::isValid($word)) {
					$betterLocationsObjects[] = OpenLocationCodeService::parseCoords($word);
				}
			} catch (\Exception $exception) {
				$betterLocationsObjects[] = $exception;
			}
		}

		// What Three Word
		if (preg_match_all(WhatThreeWordService::RE_IN_STRING, $messageWithoutUrls, $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$words = $matches[0][$i];
				try {
					if (WhatThreeWordService::isWords($words)) {
						$betterLocationsObjects[] = WhatThreeWordService::parseCoords($words);
					}
				} catch (\Exception $exception) {
					$betterLocationsObjects[] = $exception;
				}
			}
		}

		return $betterLocationsObjects;
	}

	private static function getMessageWithoutUrls(string $text, array $entities) {
		foreach (array_reverse($entities) as $entity) {
			if ($entity->type === 'url') {
				$text = General::substrReplace($text, str_pad('|', $entity->length), $entity->offset, $entity->length);
			}
		}
		return $text;
	}

	public function generateBetterLocation() {
		$links = [
			sprintf('<a href="%s">Google</a>', GoogleMapsService::getLink($this->lat, $this->lon)),
			sprintf('<a href="%s">Mapy.cz</a>', MapyCzService::getLink($this->lat, $this->lon)),
			sprintf('<a href="%s">Waze</a>', WazeService::getLink($this->lat, $this->lon, true)),
			sprintf('<a href="%s">OSM</a>', OpenStreetMapService::getLink($this->lat, $this->lon)),
			sprintf('<a href="%s">Intel</a>', IngressIntelService::getLink($this->lat, $this->lon)),
		];

		return sprintf('%s %s <code>%f,%f</code>:%s%s', $this->prefixMessage, Icons::SUCCESS, $this->lat, $this->lon, PHP_EOL, join(' | ', $links)) . PHP_EOL . PHP_EOL;
	}
}
