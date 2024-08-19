<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\StringUtils;
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
			preg_match('/^\/[a-z]{2}\/(?:place|lieu)\/([0-9]+)/', $this->url->getPath(), $matches)
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
				'<a href="%s">%s</a> - <a href="%s">%s</a>',
				$this->inputUrl,
				self::NAME,
				$cleanEnUrl,
				$placeName,
			));
		}

		$description = self::extractDescription($finder);
		if ($description !== null) {
			$location->addDescription($description);
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

	private static function extractDescription(\DOMXPath $finder): ?string
	{
		$xpaths = [
			'//div[contains(@class, "place-info-description")]/p[@lang="en"]', // English
			'//div[contains(@class, "place-info-description")]/p', // First available language
		];

		foreach ($xpaths as $xpath) {
			$placeDescriptionEn = $finder->query($xpath);
			if ($placeDescriptionEn->count() === 0) {
				continue;
			}

			$description = $placeDescriptionEn->item(0)->textContent;
			$description = htmlspecialchars($description);
			$description = StringUtils::replaceNewlines($description, '');
			if (mb_strlen($description) > 250) {
				$description = trim(mb_substr($description, 0, 200)) . StringUtils::ELLIPSIS;
			}
			return $description;
		}
		return null;
	}
}
