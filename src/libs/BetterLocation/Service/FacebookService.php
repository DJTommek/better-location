<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\MiniCurl\Response;
use App\Utils\Coordinates;
use Nette\Http\Url;
use Nette\Utils\Arrays;

final class FacebookService extends AbstractService
{
	const ID = 6;
	const NAME = 'Facebook';

	const LINK = 'https://facebook.com';

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'facebook.com' &&
			preg_match('/^\/[^\/]+/', $this->url->getPath()) // at least "/a"
		);
	}

	public function process(): void
	{
		$pageHomeUrl = $this->getPageHomeUrl();
		$response = (new MiniCurl($pageHomeUrl->getAbsoluteUrl()))
			->allowCache(Config::CACHE_TTL_FACEBOOK)
			->setHttpHeader('accept', 'text/html')
			->run();

		if ($coords = $this->findCoordinatesInJson($response) ?? $this->findCoordinatesFromMapMarker($response)) {
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class);
			if ($pageData = self::parseLdJson($response->getBody())) {
				switch ($pageData->{'@type'}) {
					case 'BreadcrumbList':
						$pageName = Arrays::last($pageData->itemListElement)->name;
						$location->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::getName(), $pageHomeUrl, $pageName));
						break;
					case 'LocalBusiness':
						$location->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::getName(), $pageHomeUrl, $pageData->name));
						$location->setAddress($pageData->address->streetAddress . ', ' . $pageData->address->addressLocality);
						if (isset($pageData->telephone)) {
							$location->setDescription($pageData->telephone);
						}
						break;
				}
			}
			$this->collection->add($location);
		}
	}

	/**
	 * Generate URL to load homepage of some page instead of photos, reviews, etc since location is only on main page.
	 * Also, if mobile version of page is requested, load desktop version instead since it is different in many ways
	 * @example https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal -> https://facebook.com/Biggie-Express-251025431718109
	 */
	private function getPageHomeUrl(): Url
	{
		$urlToRequest = new Url($this->url);
		$urlToRequest->setQuery('');
		$explodedPath = explode('/', $urlToRequest->getPath());
		$newPath = join('/', array_slice($explodedPath, 0, 2)); // get only first part of path
		$urlToRequest->setPath($newPath);
		$urlToRequest->setHost('facebook.com');
		return $urlToRequest;
	}

	private static function parseLdJson(string $response): ?\stdClass
	{
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
//		$name = trim($finder->query('//h1[@id="seo_h1_tag"]/span')->item(0)->textContent);
		$jsonEl = $finder->query('//script[@type="application/ld+json"]')->item(0);
		return $jsonEl ? json_decode($jsonEl->textContent, false, 512, JSON_THROW_ON_ERROR) : null;
	}

	/**
	 * JSON hidden deep in HTML and JSON structure, easier to bruteforce it via regex, example:
	 * ...true, "latitude": 50.087244375083, "longitude": 14.469230175018, "...
	 *
	 * @return ?Coordinates
	 */
	private function findCoordinatesInJson(Response $response): ?Coordinates
	{
		if (
			preg_match('/"latitude"\s*:\s*(-?[0-9.]+)/', $response->getBody(), $matchesLat)
			&&
			preg_match('/"longitude"\s*:\s*(-?[0-9.]+)/', $response->getBody(), $matchesLon)
		) {
			if (Coordinates::isLat($matchesLat[1]) && Coordinates::isLon($matchesLon[1])) {
				return new Coordinates($matchesLat[1], $matchesLon[1]);
			}
		}
		return null;
	}

	/**
	 * Try to find coordinates hidden in static map URL, where are defined as marker, example:
	 * https://external.fprg5-1.fna.fbcdn.net/static_map.php?v=2023&amp;ccb=4-4&amp;size=306x98&amp;zoom=15&amp;markers=50.06179000%2C14.43703000&amp;language=cs_CZ
	 */
	private function findCoordinatesFromMapMarker(Response $response): ?Coordinates
	{
		if (preg_match('/markers=(-?[0-9.]+)%2C(-?[0-9.]+)/', $response->getBody(), $matches)) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				return new Coordinates($matches[1], $matches[2]);
			}
		}
		return null;
	}
}
