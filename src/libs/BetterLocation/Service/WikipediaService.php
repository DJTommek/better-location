<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use Nette\Utils\Strings;

final class WikipediaService extends AbstractService
{
	const ID = 20;
	const NAME = 'Wikipedia';

	const LINK = 'https://wikipedia.org';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'wikipedia.org' &&
			(
				Strings::startsWith($this->url->getPath(), '/wiki/') ||
				Strings::startsWith($this->url->getPath(), '/w/')
			)
		);
	}

	public function process(): void
	{
		$response = $this->requestLocationFromWikipediaPage();
		if (isset($response->wgCoordinates) && Coordinates::isLat($response->wgCoordinates->lat) && Coordinates::isLon($response->wgCoordinates->lon)) {
			$location = new BetterLocation($this->inputUrl, $response->wgCoordinates->lat, $response->wgCoordinates->lon, self::class);
			$location->setPrefixMessage(sprintf('<a href="%s">Wikipedia %s</a>', $this->inputUrl, htmlspecialchars($response->wgTitle, ENT_NOQUOTES)));
			$this->collection->add($location);
		}
	}

	private function requestLocationFromWikipediaPage(): \stdClass
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_WIKIPEDIA)->run()->getBody();
		$startString = '<script>document.documentElement.className="client-js";RLCONF=';
		$endString = 'RLSTATE=';
		$posStart = mb_strpos($response, $startString);
		$posEnd = mb_strpos($response, $endString);
		$jsonText = mb_substr(
			$response,
			mb_strlen($startString) + $posStart,
			$posEnd - $posStart - mb_strlen($startString),
		);
		$jsonText = rtrim($jsonText, " \t\n\r\0\x0B;"); // default whitespace trim and ;
		$jsonText = preg_replace('/:\s?!0/', ':false', $jsonText); // replace !0 with false
		$jsonText = preg_replace('/:\s?!1/', ':true', $jsonText); // replace !1 with true
		return json_decode($jsonText, false, 512, JSON_THROW_ON_ERROR);
	}
}
