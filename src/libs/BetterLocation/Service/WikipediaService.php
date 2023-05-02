<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Http\HttpClient;
use DJTommek\Coordinates\Coordinates;
use Nette\Utils\Json;

final class WikipediaService extends AbstractService
{
	const ID = 20;
	const NAME = 'Wikipedia';

	const LINK = 'https://wikipedia.org';

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'wikipedia.org' &&
			(
				str_starts_with($this->url->getPath(), '/wiki/') ||
				str_starts_with($this->url->getPath(), '/w/')
			)
		);
	}

	public function process(): void
	{
		$response = $this->requestLocationFromWikipediaPage();
		if (isset($response->wgCoordinates) === false) {
			return;
		}

		$coords = Coordinates::safe($response->wgCoordinates->lat, $response->wgCoordinates->lon);
		if ($coords === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
		$location->setPrefixMessage(sprintf('<a href="%s">Wikipedia %s</a>', $this->inputUrl, htmlspecialchars($response->wgTitle, ENT_NOQUOTES)));
		$this->collection->add($location);
	}

	private function requestLocationFromWikipediaPage(): \stdClass
	{
		$httpClient = new HttpClient();
		$httpClient->allowCache(Config::CACHE_TTL_WIKIPEDIA);
		$httpClient->setHttpHeader('a', 'b');
		$body = (string)$httpClient->get($this->url)->getBody();

		$startString = ';RLCONF=';
		$endString = ';RLSTATE=';
		$posStart = mb_strpos($body, $startString);
		$posEnd = mb_strpos($body, $endString);
		$jsonText = mb_substr(
			$body,
			mb_strlen($startString) + $posStart,
			$posEnd - $posStart - mb_strlen($startString),
		);
		$jsonText = rtrim($jsonText, " \t\n\r\0\x0B;"); // default whitespace trim and ;
		$jsonText = preg_replace('/:\s?!0/', ':false', $jsonText); // replace !0 with false
		$jsonText = preg_replace('/:\s?!1/', ':true', $jsonText); // replace !1 with true
		return Json::decode($jsonText);
	}
}
