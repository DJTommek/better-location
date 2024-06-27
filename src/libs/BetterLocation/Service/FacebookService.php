<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use DJTommek\Coordinates\Coordinates;
use Nette\Http\Url;
use Nette\Utils\Arrays;

final class FacebookService extends AbstractService
{
	const ID = 6;
	const NAME = 'Facebook';

	const LINK = 'https://facebook.com';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'facebook.com' &&
			preg_match('/^\/[^\/]+/', $this->url->getPath()) // at least "/a"
		);
	}

	public function process(): void
	{
		$pageAboutUrl = $this->getPageAboutUrl();
		$body = $this->requestor->get($pageAboutUrl, Config::CACHE_TTL_FACEBOOK, headers: ['accept' => 'text/html']);

		if ($coords = $this->findCoordinatesInJson($body) ?? $this->findCoordinatesFromMapMarker($body)) {
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class);
			if ($pageData = self::parseLdJson($body)) {
				switch ($pageData->{'@type'}) {
					case 'BreadcrumbList':
						$pageName = Arrays::last($pageData->itemListElement)->name;
						$location->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::getName(), $pageAboutUrl, $pageName));
						break;
					case 'LocalBusiness':
						$location->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::getName(), $pageAboutUrl, $pageData->name));
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
	 *
	 * @example https://www.facebook.com/Biggie-Express-251025431718109/about/?ref=page_internal -> https://facebook.com/Biggie-Express-251025431718109
	 */
	private function getPageAboutUrl(): Url
	{
		$urlToRequest = new Url($this->url);
		$urlToRequest->setQuery('');
		$explodedPath = explode('/', $urlToRequest->getPath());
		$newPath = join('/', array_slice($explodedPath, 0, 2)); // get only first part of path
		$newPath .= '/about';
		$urlToRequest->setPath($newPath);
		$urlToRequest->setScheme('https');
		$urlToRequest->setHost('www.facebook.com');
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
	private function findCoordinatesInJson(string $body): ?Coordinates
	{
		if (
			preg_match('/"latitude"\s*:\s*(-?[0-9.]+)/', $body, $matchesLat)
			&&
			preg_match('/"longitude"\s*:\s*(-?[0-9.]+)/', $body, $matchesLon)
		) {
			return Coordinates::safe($matchesLat[1], $matchesLon[1]);
		}
		return null;
	}

	/**
	 * Try to find coordinates hidden in static map URL, where are defined as marker, example:
	 * https://external.fprg5-1.fna.fbcdn.net/static_map.php?v=2023&amp;ccb=4-4&amp;size=306x98&amp;zoom=15&amp;markers=50.06179000%2C14.43703000&amp;language=cs_CZ
	 */
	private function findCoordinatesFromMapMarker(string $body): ?Coordinates
	{
		if (preg_match('/markers=(-?[0-9.]+)%2C(-?[0-9.]+)/', $body, $matches)) {
			return Coordinates::safe($matches[1], $matches[2]);
		}
		return null;
	}
}
