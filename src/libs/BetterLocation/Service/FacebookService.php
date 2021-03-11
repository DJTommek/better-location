<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Nette\Http\Url;

final class FacebookService extends AbstractService
{
	const NAME = 'Facebook';

	const LINK = 'https://facebook.com';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
		}
	}

	public function isValid(): bool
	{
		return (
			$this->url->getDomain(2) === 'facebook.com' &&
			preg_match('/^\/[^\/]+/', $this->url->getPath()) // at least "/a"
		);
	}

	public function process(): void
	{
		$pageHomeUrl = $this->getPageHomeUrl();
		$response = (new MiniCurl($pageHomeUrl->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_FACEBOOK)->run()->getBody();
		if (preg_match('/markers=(-?[0-9.]+)%2C(-?[0-9.]+)/', $response, $matches)) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				$location = new BetterLocation($this->inputUrl, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class);
				$pageData = self::parsePageData($response);
				$location->setPrefixMessage(sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::getName(), $pageHomeUrl, $pageData->name));
				$location->setAddress($pageData->address->streetAddress . ', ' . $pageData->address->addressLocality);
				if (isset($pageData->telephone)) {
					$location->setDescription($pageData->telephone);
				}
				$this->collection->add($location);
			}
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

	private static function parsePageData(string $response): \stdClass
	{
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
//		$name = trim($finder->query('//h1[@id="seo_h1_tag"]/span')->item(0)->textContent);
		$ldJson = $finder->query('//script[@type="application/ld+json"]')->item(0)->textContent;
		return json_decode($ldJson, false, 512, JSON_THROW_ON_ERROR);
	}
}
