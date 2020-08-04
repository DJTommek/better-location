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
use unreal4u\TelegramAPI\Telegram\Types\Inline\Keyboard\Button;
use Utils\Coordinates;
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
						$googleMapsBetterLocations = GoogleMapsService::parseCoordsMultiple($url);
						if ($googleMapsBetterLocations > 1) {
							if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_STREET_VIEW])) {
								$mainLocationKey = GoogleMapsService::TYPE_STREET_VIEW;
							} else if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_PLACE])) {
								$mainLocationKey = GoogleMapsService::TYPE_PLACE;
							} else if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_HIDDEN])) {
								$mainLocationKey = GoogleMapsService::TYPE_HIDDEN;
							} else if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_SEARCH])) {
								$mainLocationKey = GoogleMapsService::TYPE_SEARCH;
							} else if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_UNKNOWN])) {
								$mainLocationKey = GoogleMapsService::TYPE_UNKNOWN;
							} else if (isset($googleMapsBetterLocations[GoogleMapsService::TYPE_MAP])) {
								$mainLocationKey = GoogleMapsService::TYPE_MAP;
							} else {
								throw new \Exception('Error while selecting main location: Probably added new GoogleMaps type?');
							}
							$mainLocation = $googleMapsBetterLocations[$mainLocationKey];
							foreach ($googleMapsBetterLocations as $key => $googleMapsBetterLocation) {
								if ($key === $mainLocationKey) {
									continue;
								} else {
									$distance = Coordinates::distance(
										$mainLocation->getLat(),
										$mainLocation->getLon(),
										$googleMapsBetterLocation->getLat(),
										$googleMapsBetterLocation->getLon(),
									);
									if ($distance < DISTANCE_IGNORE) {
										// Remove locations that are too close to main location
										unset($googleMapsBetterLocations[$key]);
									} else {
										$googleMapsBetterLocation->setDescription(sprintf('%s Location is %d meters away from %s %s.', Icons::WARNING, $distance, $mainLocationKey, Icons::ARROW_UP));
									}
								}
							}
						}
						// Keys needs to be reset, otherwise it would override themselves
						$betterLocationsObjects = array_merge($betterLocationsObjects, array_values($googleMapsBetterLocations));
					} else if (MapyCzService::isValid($url)) {
						$mapyCzBetterLocations = MapyCzService::parseCoordsMultiple($url);
						if ($mapyCzBetterLocations > 1) {
							if (isset($mapyCzBetterLocations[MapyCzService::TYPE_PANORAMA])) {
								$mainLocationKey = MapyCzService::TYPE_PANORAMA;
							} else if (isset($mapyCzBetterLocations[MapyCzService::TYPE_PLACE_ID])) {
								$mainLocationKey = MapyCzService::TYPE_PLACE_ID;
							} else if (isset($mapyCzBetterLocations[MapyCzService::TYPE_PLACE_COORDS])) {
								$mainLocationKey = MapyCzService::TYPE_PLACE_COORDS;
							} else if (isset($mapyCzBetterLocations[MapyCzService::TYPE_MAP])) {
								$mainLocationKey = MapyCzService::TYPE_MAP;
							} else {
								throw new \Exception('Error while selecting main location: Probably added new MapyCz type?');
							}
							$mainLocation = $mapyCzBetterLocations[$mainLocationKey];
							foreach ($mapyCzBetterLocations as $key => $mapyCzBetterLocation) {
								if ($key === $mainLocationKey) {
									continue;
								} else {
									$distance = Coordinates::distance(
										$mainLocation->getLat(),
										$mainLocation->getLon(),
										$mapyCzBetterLocation->getLat(),
										$mapyCzBetterLocation->getLon(),
									);
									if ($distance < DISTANCE_IGNORE) {
										// Remove locations that are too close to main location
										unset($mapyCzBetterLocations[$key]);
									} else {
										$mapyCzBetterLocation->setDescription(sprintf('%s Location is %d meters away from %s %s.', Icons::WARNING, $distance, $mainLocationKey, Icons::ARROW_UP));
									}
								}
							}
						}
						// Keys needs to be reset, otherwise it would override themselves
						$betterLocationsObjects = array_merge($betterLocationsObjects, array_values($mapyCzBetterLocations));
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

	public function export(): array {
		return [
			'lat' => $this->getLat(),
			'lon' => $this->getLon(),
			'service' => strip_tags($this->getPrefixMessage()),
		];
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

	public function generateDriveButtons() {
		$googleButton = new Button();
		$googleButton->text = 'Google ' . Icons::CAR;
		$googleButton->url = $this->getLink(new GoogleMapsService, true);

		$wazeButton = new Button();
		$wazeButton->text = 'Waze ' . Icons::CAR;
		$wazeButton->url = $this->getLink(new WazeService(), true);

		return [$googleButton, $wazeButton];
	}


	/**
	 * @param string $prefixMessage
	 */
	public function setPrefixMessage(string $prefixMessage): void {
		$this->prefixMessage = $prefixMessage;
	}

	/**
	 * @return mixed
	 */
	public function getPrefixMessage() {
		return $this->prefixMessage;
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
