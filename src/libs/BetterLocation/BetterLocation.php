<?php

declare(strict_types=1);

namespace BetterLocation;

use \BetterLocation\Service\GoogleMapsService;
use \BetterLocation\Service\MapyCzService;
use BetterLocation\Service\OpenStreetMapService;
use \Utils\Coordinates;
use \Utils\General;
use \Icons;
use \OpenLocationCode\OpenLocationCode;

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
	public static function generateFromMessage(string $text, array $entities): array {
		$betterLocationsObjects = [];
		// @TODO remove this ugly dummy...
		$dummyBetterLocation = new BetterLocation(49.0, 15.0, '');

		$index = 0;
		$result = '';
		foreach ($entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				if ($entity->type === 'url') {
					$url = mb_substr($text, $entity->offset, $entity->length);
				} else if ($entity->type === 'text_link') {
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
				}

				// OLC (Open Location Codes, Plus Codes)
				// https://plus.codes/8FXP74WG+XHW
				$plusCodesLink = 'https://plus.codes/';
				if (substr($url, 0, mb_strlen($plusCodesLink)) === $plusCodesLink) {
					$plusCode = str_replace($plusCodesLink, '', $url);
					if (OpenLocationCode::isValid($plusCode)) {
						$coords = OpenLocationCode::decode($plusCode);
						$betterLocationsObjects[] = new BetterLocation(
							$coords['latitudeCenter'],
							$coords['longitudeCenter'],
							sprintf('<a href="%s">#%d (OLC:%s</a>): ', $plusCodesLink, ++$index, $plusCode)
						);
					} else {
						$result .= sprintf('%s Detected plus code URL but word is not valid.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
				}

				// @TODO possibly remove, this is being detected by string, no need to match URLs
				// W3W (What Three Words)
				// https://w3w.co/chladná.naopak.vložit
				// https://what3words.com/define.readings.cucumber
				// chladná.naopak.vložit
				// flicks.gazed.tapes
				// https://developer.what3words.com/tutorial/detecting-if-text-is-in-the-format-of-a-3-word-address/
//				$what3wordsLink = 'https://w3w.co/';
//				$what3wordsLinkShort = 'https://what3words.com/';
//				if (
//					substr($url, 0, mb_strlen($what3wordsLink)) === $what3wordsLink ||
//					substr($url, 0, mb_strlen($what3wordsLinkShort)) === $what3wordsLinkShort
//				) {
//					$words = str_replace($what3wordsLink, '', $url);
//					$words = str_replace($what3wordsLinkShort, '', $words);
//					$w3wApiKey = 'Z6OBR7ZI';
//					$apiLink = sprintf('https://api.what3words.com/v3/convert-to-coordinates?key=%s&words=%s&format=json', $w3wApiKey, urlencode($words));
//					$data = \GuzzleHttp\json_decode(General::fileGetContents($apiLink));
//					if (true) {
//						$result .= sprintf('<a href="%s">#%d (W3W:%s</a>): ', $data->map, ++$index, $data->words);
//						$result .= $this->generateBetterLocation($data->coordinates->lat, $data->coordinates->lng);
//					}
//				}

				$wazeLink = 'https://www.waze.com';
				// Waze short link:
				// https://waze.com/ul/hu2fk8zezt
				if (preg_match('/https:\/\/waze\.com\/ul\/[a-z0-9A-Z]+$/', $url)) {
					// first letter "h" is removed
					$wazeUpdatedUrl = str_replace('waze.com/ul/h', 'www.waze.com/livemap?h=', $url);
					$newLocation = $dummyBetterLocation->getLocationFromHeaders($wazeUpdatedUrl);
					if ($newLocation) {
						$newLocation = $wazeLink . $newLocation;
						$coords = $dummyBetterLocation->getCoordsFromWaze($newLocation);
						if ($coords) {
							$betterLocationsObjects[] = new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">#%d (Waze)</a>: ', $url, ++$index));
						} else {
							$result .= sprintf('%s Unable to get coords for Waze short link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
						}
					} else {
						$result .= sprintf('%s Unable to get real url for Waze short link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
					// Waze other links:
					// https://www.waze.com/ul?ll=50.06300713%2C14.43964005&navigate=yes&zoom=15
					// https://www.waze.com/ul?ll=49.87707960%2C18.43036300&navigate=yes
					// https://www.waze.com/ul?ll=50.06300713%2C14.43964005
					//
					// https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016&utm_campaign=waze_website&utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=https%3A%2F%2Fwww.waze.com%2Fcs%2Faccount&utm_source=waze_website
					// https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016
					//
					// https://www.waze.com/cs/livemap/directions?utm_expid=.K6QI8s_pTz6FfRdYRPpI3A.0&utm_referrer=&to=ll.50.07734439%2C14.43475842
					// https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842
					// https://www.waze.com/cs/livemap/directions?to=ll.49.8770796%2C18.430363
				} else if (substr($url, 0, mb_strlen($wazeLink)) === $wazeLink) {
					$coords = $dummyBetterLocation->getCoordsFromWaze($url);
					if ($coords) {
						$betterLocationsObjects[] = new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">#%d (Waze)</a>: ', $url, ++$index));
					} else {
						$result .= sprintf('%s Unable to get coords for Waze link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
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

		// OLC (Open Location Codes, Plus Codes)
		foreach (preg_split('/[^a-zA-Z0-9+]+/', $dummyBetterLocation->getTextWithoutUrls($text, $entities)) as $word) {
			if (OpenLocationCode::isValid($word)) {
				$coords = OpenLocationCode::decode($word);
				$plusCodesLink = sprintf('https://plus.codes/%s', $word);
				$betterLocationsObjects[] = new BetterLocation($coords['latitudeCenter'], $coords['longitudeCenter'], sprintf('<a href="%s">#%d (OLC:%s</a>): ', $plusCodesLink, ++$index, $word));
			}
		}

		// W3W (What Three Words)
		// https://w3w.co/chladná.naopak.vložit
		// https://what3words.com/define.readings.cucumber
		// chladná.naopak.vložit
		// flicks.gazed.tapes
		// https://developer.what3words.com/tutorial/detecting-if-text-is-in-the-format-of-a-3-word-address/
//		$what3wordsRegex = '/\/*((?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,}[・.。](?:\p{L}\p{M}*){1,})/u';
//		if (preg_match_all($what3wordsRegex, $this->text, $matches)) {
//			$w3wApiKey = 'Z6OBR7ZI';
//			foreach ($matches[1] as $words) {
//				$apiLink = sprintf('https://api.what3words.com/v3/convert-to-coordinates?key=%s&words=%s&format=json', $w3wApiKey, urlencode($words));
//				$data = json_decode(General::fileGetContents($apiLink));
//				if (isset($data->error)) {
//					// @TODO temporary disabled because it has false-positive matches, eg www.viribusunitis.cz
//					// $result .= sprintf('%s Detected What3Words "%s" but unable to get coordinates.', Icons::ERROR, urlencode($words)) . PHP_EOL . PHP_EOL;
//				} else {
//					$result .= sprintf('<a href="%s">#%d (W3W:%s</a>): ', $data->map, ++$index, $data->words);
//					$result .= $this->generateBetterLocation($data->coordinates->lat, $data->coordinates->lng);
//				}
//			}
//		}

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
		$wazeLink = sprintf('https://www.waze.com/ul?ll=%1$f,%2$f&navigate=yes', $this->lat, $this->lon);
		$links[] = sprintf('<a href="%s">Waze</a>', $wazeLink);
		// OpenStreetMap
		$links[] = sprintf('<a href="%s">OSM</a>', OpenStreetMapService::getLink($this->lat, $this->lon));
		// Intel
		$intelLink = sprintf('https://intel.ingress.com/intel?ll=%1$f,%2$f&pll=%1$f,%2$f', $this->lat, $this->lon);
		$links[] = sprintf('<a href="%s">Intel</a>', $intelLink);

		return sprintf('%s %s <code>%f,%f</code>:%s%s', $this->prefixMessage, Icons::SUCCESS, $this->lat, $this->lon, PHP_EOL, join(' | ', $links)) . PHP_EOL . PHP_EOL;
	}

	private function getLocationFromHeaders($url) {
		$headers = General::getHeaders($url);
		return $headers['Location'] ?? null;
	}

	private function getCoordsFromWaze(string $url) {
		$paramsString = explode('?', $url);
		parse_str($paramsString[1], $params);
		if (isset($params['latlng'])) {
			$coords = explode(',', $params['latlng']);
		} else if (isset($params['ll'])) {
			$coords = explode(',', $params['ll']);
		} else if (isset($params['to'])) {
			$coords = explode(',', $params['to']);
			$coords[0] = str_replace('ll.', '', $coords[0]);
		} else {
			return null;
		}
		return [
			floatval($coords[0]),
			floatval($coords[1]),
		];
	}
}
