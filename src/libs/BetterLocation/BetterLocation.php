<?php

declare(strict_types=1);

namespace BetterLocation;

use \BetterLocation\Service\GoogleMapsService;
use \Utils\Coordinates;
use \Utils\General;
use \Icons;
use \OpenLocationCode\OpenLocationCode;

class BetterLocation
{
	private $text;
	private $entities;

	const TELEGRAM_GROUP_WHITELIST = [
		-1001493272809, // redilap test group
		-1001404725560, // iQuest 2020 chat
	];

	public function __construct(string $text, array $entities) {
		$this->text = $text;
		$this->entities = $entities;
	}

	/**
	 * @return string
	 * @throws \Exception
	 */
	public function processMessage() {
		$index = 0;
		$result = '';
		foreach ($this->entities as $entity) {
			if (in_array($entity->type, ['url', 'text_link'])) {
				if ($entity->type === 'url') {
					$url = mb_substr($this->text, $entity->offset, $entity->length);
				} else if ($entity->type === 'text_link') {
					$url = $entity->url;
				} else {
					throw new \Exception('Unhandled entity type');
				}

				$googleMapsService = new GoogleMapsService($url);
				$googleMapsServiceResult = $googleMapsService->run();
				if ($googleMapsServiceResult) {
					$result .= sprintf('<a href="%s">#%d (Google)</a>: ', $url, ++$index);
					$result .= $googleMapsServiceResult;
				}

				// Mapy.cz short link:
				// https://mapy.cz/s/porumejene
				// https://en.mapy.cz/s/porumejene
				// https://en.mapy.cz/s/3ql7u
				if (preg_match('/https:\/\/([a-z]{1,3}\.)?mapy\.cz\/s\/[a-z0-9A-Z]+/', $url)) {
					$result .= sprintf('<a href="%s">#%d (Mapy.cz)</a>: ', $url, ++$index);
					$newLocation = $this->getLocationFromHeaders($url);
					if ($newLocation) {
						$coords = $this->getCoordsFromMapyCz($newLocation);
						if ($coords) {
							$result .= $this->generateBetterLocation($coords[0], $coords[1]);
						} else {
							$result .= sprintf('%s Unable to get coords for Mapy.cz short link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
						}
					} else {
						$result .= sprintf('%s Unable to get real url for Mapy.cz short link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
				}

				// Mapy.cz normal link:
				// https://en.mapy.cz/zakladni?x=14.2991869&y=49.0999235&z=16&pano=1&source=firm&id=350556
				// https://mapy.cz/?x=15.278244&y=49.691235&z=15&ma_x=15.278244&ma_y=49.691235&ma_t=Jsem+tady%2C+otev%C5%99i+odkaz&source=coor&id=15.278244%2C49.691235
				// Mapy.cz panorama:
				// https://en.mapy.cz/zakladni?x=14.3139613&y=49.1487367&z=15&pano=1&pid=30158941&yaw=1.813&fov=1.257&pitch=-0.026
				if (preg_match('/https:\/\/([a-z]{1,3}\.)?mapy\.cz\/([a-z0-9-]{2,})?\?/', $url)) { // at least two characters, otherwise it is probably /s/hort-version of link
					$result .= sprintf('<a href="%s">#%d (Mapy.cz)</a>: ', $url, ++$index);
					$coords = $this->getCoordsFromMapyCz($url);
					if ($coords) {
						$result .= $this->generateBetterLocation($coords[0], $coords[1]);
					} else {
						$result .= sprintf('%s Unable to get coords from Mapy.cz normal link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
				}

				// OpenStreetMap:
				// https://www.openstreetmap.org/#map=17/49.355164/14.272819
				// https://www.openstreetmap.org/#map=17/49.32085/14.16402&layers=N
				// https://www.openstreetmap.org/#map=18/50.05215/14.45283
				// https://www.openstreetmap.org/?mlat=50.05215&mlon=14.45283#map=18/50.05215/14.45283
				// https://www.openstreetmap.org/?mlat=50.05328&mlon=14.45640#map=18/50.05328/14.45640
				$openStreetMapUrl = 'https://www.openstreetmap.org/';
				if (substr($url, 0, mb_strlen($openStreetMapUrl)) === $openStreetMapUrl) {
					$result .= sprintf('<a href="%s">#%d (OSM)</a>: ', $url, ++$index);
					$coords = $this->getCoordsFromOpenStreetMap($url);
					if ($coords) {
						$result .= $this->generateBetterLocation($coords[0], $coords[1]);
					} else {
						$result .= sprintf('%s Unable to get coords from OSM basic link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
				}

				// OpenStreetMap short link:
				// https://osm.org/go/0J0kf83sQ--?m=
				// https://osm.org/go/0EEQjE==
				// https://osm.org/go/0EEQjEEb
				// https://osm.org/go/0J0kf3lAU--
				// https://osm.org/go/0J0kf3lAU--?m=
				$openStreetMapShortUrl = 'https://osm.org/go/';
				if (substr($url, 0, mb_strlen($openStreetMapShortUrl)) === $openStreetMapShortUrl) {
					$result .= sprintf('<a href="%s">#%d (OSM)</a>: ', $url, ++$index);
					$result .= sprintf('%s Short URL of OSM maps is not yet implemented.', Icons::ERROR) . PHP_EOL . PHP_EOL;
				}

				// OLC (Open Location Codes, Plus Codes)
				// https://plus.codes/8FXP74WG+XHW
				$plusCodesLink = 'https://plus.codes/';
				if (substr($url, 0, mb_strlen($plusCodesLink)) === $plusCodesLink) {
					$plusCode = str_replace($plusCodesLink, '', $url);
					if (OpenLocationCode::isValid($plusCode)) {
						$coords = OpenLocationCode::decode($plusCode);
						$result .= sprintf('<a href="%s">#%d (OLC:%s</a>): ', $plusCodesLink, ++$index, $plusCode);
						$result .= $this->generateBetterLocation($coords['latitudeCenter'], $coords['longitudeCenter']);
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
					$result .= sprintf('<a href="%s">#%d (Waze)</a>: ', $url, ++$index);
					// first letter "h" is removed
					$wazeUpdatedUrl = str_replace('waze.com/ul/h', 'www.waze.com/livemap?h=', $url);
					$newLocation = $this->getLocationFromHeaders($wazeUpdatedUrl);
					if ($newLocation) {
						$newLocation = $wazeLink . $newLocation;
						$coords = $this->getCoordsFromWaze($newLocation);
						if ($coords) {
							$result .= $this->generateBetterLocation($coords[0], $coords[1]);
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
					$result .= sprintf('<a href="%s">#%d (Waze)</a>: ', $url, ++$index);
					$coords = $this->getCoordsFromWaze($url);
					if ($coords) {
						$result .= $this->generateBetterLocation($coords[0], $coords[1]);
					} else {
						$result .= sprintf('%s Unable to get coords for Waze link.', Icons::ERROR) . PHP_EOL . PHP_EOL;
					}
				}
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES, $this->getTextWithoutUrls(), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$result .= sprintf('#%d (Coords): ', ++$index);
				$result .= $this->generateBetterLocation(floatval($matches[1][$i]), floatval($matches[2][$i]));
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES, $this->getTextWithoutUrls(), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$result .= sprintf('#%d (Coords): ', ++$index);
				$result .= $this->generateBetterLocation(
					Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[2][$i]),
					Coordinates::wgs84DegreesMinutesToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), $matches[6][$i]),
				);
			}
		}

		// Coordinates
		if (preg_match_all(Coordinates::RE_WGS84_DEGREES_MINUTES_SECONDS, $this->getTextWithoutUrls(), $matches)) {
			for ($i = 0; $i < count($matches[0]); $i++) {
				$result .= sprintf('#%d (Coords): ', ++$index);
				$result .= $this->generateBetterLocation(
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[2][$i]), floatval($matches[3][$i]), floatval($matches[4][$i]), $matches[5][$i]),
					Coordinates::wgs84DegreesMinutesSecondsToDecimal(floatval($matches[7][$i]), floatval($matches[8][$i]), floatval($matches[9][$i]), $matches[10][$i]),
				);
			}
		}

		// OLC (Open Location Codes, Plus Codes)
		foreach (preg_split('/[^a-zA-Z0-9+]+/', $this->getTextWithoutUrls()) as $word) {
			if (OpenLocationCode::isValid($word)) {
				$coords = OpenLocationCode::decode($word);
				$plusCodesLink = sprintf('https://plus.codes/%s', $word);
				$result .= sprintf('<a href="%s">#%d (OLC:%s</a>): ', $plusCodesLink, ++$index, $word);
				$result .= $this->generateBetterLocation($coords['latitudeCenter'], $coords['longitudeCenter']);
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

		return $result;
	}

	private function getTextWithoutUrls() {
		$textWithoutUrls = $this->text;
		foreach (array_reverse($this->entities) as $entity) {
			if ($entity->type === 'url') {
				$textWithoutUrls = General::substrReplace($textWithoutUrls, '<a>', $entity->offset, $entity->length);
			}
		}
		return $textWithoutUrls;
	}

	public function generateBetterLocation(float $lat, float $lon) {
		$links = [];
		// Google maps
		$googleLink = sprintf('https://www.google.cz/maps/place/%1$f,%2$f?q=%1$f,%2$f', $lat, $lon);
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

	private function getLocationFromHeaders($url) {
		$headers = General::getHeaders($url);
		return $headers['Location'] ?? null;
	}

	private function getCoordsFromMapyCz(string $url) {
		$paramsString = explode('?', $url);
		parse_str($paramsString[1], $params);
		return [
			floatval($params['y']),
			floatval($params['x']),
		];
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

	private function getCoordsFromOpenStreetMap(string $url) {
		$paramsHashString = explode('#map=', $url);
		// url is in format some-url/blahblah#map=lat/lon
		if (count($paramsHashString) === 2) {
			$urlCoords = explode('/', $paramsHashString[1]);
			$resultCoords = [
				floatval($urlCoords[1]),
			];
			$coordLon = explode('&', $urlCoords[1]);
			$resultCoords[] = floatval($coordLon[0]);
			return $resultCoords;
		} else {
			$paramsQueryString = explode('?', $url);
			if (count($paramsQueryString) === 2) {
				parse_str($paramsQueryString[1], $params);
				return [
					floatval($params['mlat']),
					floatval($params['mlon']),
				];
			}
		}
		return null;
	}

	/**
	 * @param string $url
	 * @return array|null
	 * @throws \Exception
	 */
	private function getCoordsFromGoogleMaps(string $url) {
		$paramsString = explode('?', $url);
		if (count($paramsString) === 2) {
			parse_str($paramsString[1], $params);
		}
		// https://www.google.com/maps/place/50%C2%B006'04.6%22N+14%C2%B031'44.0%22E/@50.101271,14.5281082,18z/data=!3m1!4b1!4m6!3m5!1s0x0:0x0!7e2!8m2!3d50.1012711!4d14.5288824?shorturl=1
		// Regex is matching "!3d50.1012711!4d14.5288824"
		if (preg_match('/!3d(-?[0-9]{1,3}\.[0-9]+)!4d(-?[0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			return [
				floatval($matches[1]),
				floatval($matches[2]),
			];
		} else if (isset($params['ll'])) {
			$coords = explode(',', $params['ll']);
			return [
				floatval($coords[0]),
				floatval($coords[1]),
			];
		} else if (isset($params['q'])) { // @TODO in this parameter probably might be also non-coordinates locations (eg. address)
			$coords = explode(',', $params['q']);
			return [
				floatval($coords[0]),
				floatval($coords[1]),
			];
			// Warning: coordinates in URL in format "@50.00,15.00" is position of the map, not selected/shared point.
		} else if (preg_match('/@([0-9]{1,3}\.[0-9]+),([0-9]{1,3}\.[0-9]+)/', $url, $matches)) {
			return [
				floatval($matches[1]),
				floatval($matches[2]),
			];
		} else {
			// URL don't have any coordinates or place-id to translate so load content and there are some coordinates hidden in page in some of brutal multi-array
			$content = General::fileGetContents($url);
			// Regex is searching for something like this: ',"",null,[null,null,50.0641584,14.468139599999999]';
			// Warning: Not exact position
			if (preg_match('/","",null,\[null,null,(-?[0-9]{1,3}\.[0-9]+),(-?[0-9]{1,3}\.[0-9]+)]\n/', $content, $matches)) {
				return [
					floatval($matches[1]),
					floatval($matches[2]),
				];
			}
		}
		return null;
	}
}
