<?php

declare(strict_types=1);

namespace BetterLocation;

use \BetterLocation\Service\GoogleMapsService;
use \BetterLocation\Service\MapyCzService;
use \BetterLocation\Service\OpenStreetMapService;
use \BetterLocation\Service\OpenLocationCodeService;
use \BetterLocation\Service\WazeService;
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
			if (OpenLocationCodeService::isValid($word)) {
				$betterLocationsObjects[] = OpenLocationCodeService::parseCoords($word);
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
		$links[] = sprintf('<a href="%s">Waze</a>', WazeService::getLink($this->lat, $this->lon, true));
		// OpenStreetMap
		$links[] = sprintf('<a href="%s">OSM</a>', OpenStreetMapService::getLink($this->lat, $this->lon));
		// Intel
		$intelLink = sprintf('https://intel.ingress.com/intel?ll=%1$f,%2$f&pll=%1$f,%2$f', $this->lat, $this->lon);
		$links[] = sprintf('<a href="%s">Intel</a>', $intelLink);

		return sprintf('%s %s <code>%f,%f</code>:%s%s', $this->prefixMessage, Icons::SUCCESS, $this->lat, $this->lon, PHP_EOL, join(' | ', $links)) . PHP_EOL . PHP_EOL;
	}
}
