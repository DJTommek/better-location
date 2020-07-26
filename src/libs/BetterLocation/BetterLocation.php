<?php

declare(strict_types=1);

namespace BetterLocation;

use BetterLocation\Service\AbstractService;
use BetterLocation\Service\Coordinates\WG84DegreesMinutesSecondsService;
use BetterLocation\Service\Coordinates\WG84DegreesMinutesService;
use BetterLocation\Service\Coordinates\WG84DegreesService;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use \BetterLocation\Service\GoogleMapsService;
use \BetterLocation\Service\IngressIntelService;
use \BetterLocation\Service\MapyCzService;
use \BetterLocation\Service\OpenStreetMapService;
use \BetterLocation\Service\OpenLocationCodeService;
use \BetterLocation\Service\WazeService;
use \BetterLocation\Service\WhatThreeWordService;
use \Utils\General;
use \Icons;

class BetterLocation
{
	private $lat;
	private $lon;
	private $description;
	private $prefixMessage;

	/**
	 * BetterLocation constructor.
	 *
	 * @param float $lat
	 * @param float $lon
	 * @param string $prefixMessage
	 * @throws InvalidLocationException
	 */
	public function __construct(float $lat, float $lon, string $prefixMessage) {
		if ($lat > 90 || $lat < -90) {
			throw new InvalidLocationException('Latitude coordinate must be between or equal from -90 to 90 degrees.');
		}
		if ($lon > 180 || $lon < -180) {
			throw new InvalidLocationException('Longitude coordinate must be between or equal from -180 to 180 degrees.');
		}
		$this->lat = $lat;
		$this->lon = $lon;
		$this->setPrefixMessage($prefixMessage);
	}

	/**
	 * @param string $message
	 * @param array $entities
	 * @return BetterLocation[] | \InvalidArgumentException[]
	 * @throws \Exception
	 */
	public static function generateFromTelegramMessage(string $message, array $entities): array {
		$betterLocationsObjects = [];

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

		$betterLocationsObjects = array_merge($betterLocationsObjects, WG84DegreesService::findInText($messageWithoutUrls));
		$betterLocationsObjects = array_merge($betterLocationsObjects, WG84DegreesMinutesService::findInText($messageWithoutUrls));
		$betterLocationsObjects = array_merge($betterLocationsObjects, WG84DegreesMinutesSecondsService::findInText($messageWithoutUrls));

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

		$text = sprintf('%s %s <code>%f,%f</code>:%s%s', $this->prefixMessage, Icons::ARROW_RIGHT, $this->lat, $this->lon, PHP_EOL, join(' | ', $links));
		if ($this->description) {
			$text .= PHP_EOL . $this->description;
		}
		return $text . PHP_EOL . PHP_EOL;
	}

	/**
	 * @param string $prefixMessage
	 */
	public function setPrefixMessage(string $prefixMessage): void {
		$this->prefixMessage = $prefixMessage;
	}

	public function getLink($class, bool $drive = false) {
		if ($class instanceof AbstractService === false) {
			throw new \InvalidArgumentException('Class must be instance of AbstractService');
		}
		return $class::getLink($this->lat, $this->lon, $drive);
	}

	public function getLat(): float {
		return $this->lat;
	}

	public function getLon(): float {
		return $this->lon;
	}

	public function getLatLon(): array {
		return [$this->lat, $this->lon];
	}

	public function __toString() {
		return sprintf('%f, %f', $this->lat, $this->lon);
	}

	/**
	 * @param string $description
	 */
	public function setDescription(string $description): void {
		$this->description = $description;
	}
}
