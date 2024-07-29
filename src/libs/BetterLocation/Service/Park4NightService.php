<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Utils;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Http\Url;

final class Park4NightService extends AbstractService
{
	const ID = 54;
	const NAME = 'park4night';

	const LINK = 'https://park4night.com';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'park4night.com' &&
			preg_match('/^\/[a-z]{2}\/place\/([0-9]+)/', $this->url->getPath(), $matches)
		) {
			$this->data->placeId = $matches[1];
			return true;
		}

		return false;
	}

	public function process(): void
	{
		$cleanEnUrl = self::LINK . '/en/place/' . $this->data->placeId;

		$response = $this->requestor->get($cleanEnUrl, Config::CACHE_TTL_PARK4NIGHT);
		$dom = Utils::domFromUTF8($response);
		$finder = new \DOMXPath($dom);
		$googleNavigationLink = $finder->query('//a[@class="btn-itinerary"]/@href')->item(0)->textContent;
		if ($googleNavigationLink === null) {
			return;
		}

		$coords = self::extractCoordinates($googleNavigationLink);
		$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class);
		if ($placeName = self::getPlaceName($finder)) {
			$location->setPrefixMessage(sprintf(
				'<a href="%s">%s</a> - <a href="">%s</a>',
				$this->inputUrl,
				$cleanEnUrl,
				self::NAME,
				$placeName,
			));
		}
		$this->collection->add($location);
	}

	private static function getPlaceName(\DOMXPath $finder): string
	{
		$placeNameRaw = $finder->query('//h1/text()')->item(0)->textContent;
		return htmlspecialchars($placeNameRaw);
	}

	/**
	 * @TODO Google Maps service is not supporting offline processing of this URL, so do it manually in this service
	 *       here, until it is supported in Google Maps service.
	 * @example 'https://www.google.com/maps/dir/?api=1&destination=46.664101,11.159100'
	 */
	private static function extractCoordinates(string $link): CoordinatesInterface
	{
		$googleNavigationUrl = new Url($link);
		$coordsRaw = $googleNavigationUrl->getQueryParameter('destination');
		return Coordinates::fromString($coordsRaw);
	}
}
