<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\General;
use App\Utils\StringUtils;
use Tracy\Debugger;
use Tracy\ILogger;

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

	public static function isValid(string $input): bool
	{
		return false;
	}

	public static function parseCoords(string $input): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinates is not available.');
	}

	public static function isUrl(string $url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			StringUtils::endWith(mb_strtolower($parsedUrl['host']), 'facebook.com') && // supporting subdomains
			isset($parsedUrl['path']) &&
			mb_strlen($parsedUrl['path']) > 1
		);
	}

	public static function parseUrl(string $url): ?BetterLocation
	{
		try {
			$response = (new MiniCurl(self::getBaseUrl($url)))->allowCache(Config::CACHE_TTL_FACEBOOK)->run()->getBody();
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
		if (preg_match('/markers=(-?[0-9.]+)%2C(-?[0-9.]+)/', $response, $matches)) {
			$location = new BetterLocation($url, floatval($matches[1]), floatval($matches[2]), self::class);
			$pageData = self::parsePageData($response);
			$location->setPrefixMessage(sprintf('<a href="%s">%s</a> %s', $url, self::getName(), $pageData->name));
			$location->setAddress($pageData->address->streetAddress . ', ' . $pageData->address->addressLocality);
			if (isset($pageData->telephone)) {
				$location->setDescription($pageData->telephone);
			}
			return $location;
		} else {
			Debugger::log($response, ILogger::DEBUG);
			return null;
		}
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}

	/**
	 * Update URL to load homepage of some page, not photos, reviews, etc since location is only on main page.
	 * Also, if mobile version of page is requested, load desktop version instead since it is different in many ways
	 */
	private static function getBaseUrl(string $url): string
	{
		$exploded = explode('/', $url);
		$result = array_slice($exploded, 0, 4);
		if (mb_strtolower($result[2]) === 'm.facebook.com') {
			$result[2] = 'facebook.com';
		}
		return join('/', $result);
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
