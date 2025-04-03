<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\Address\Address;
use App\Address\Country;
use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Interfaces\ShareCollectionLinkInterface;
use App\BetterLocation\ServicesManager;
use App\Icons;
use App\Utils\Requestor;
use App\Utils\Strict;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;
use DJTommek\MapyCzApi;
use DJTommek\MapyCzApi\MapyCzApiException;
use DJTommek\MapyCzApi\Types\PlaceType;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;

final class MapyCzService extends AbstractService implements ShareCollectionLinkInterface
{
	const ID = 8;
	const NAME = 'Mapy.com';
	const LINK = 'https://mapy.cz';

	const TYPE_MAP_V2 = 'Map center';
	const TYPE_MAP = 'Map center';
	const TYPE_PLACE_ID = 'Place';
	const TYPE_PLACE_COORDS = 'Place coords';
	const TYPE_PANORAMA = 'Panorama';
	const TYPE_PHOTO = 'Photo';
	const TYPE_CUSTOM_POINT = 'Custom point';
	const TYPE_SEARCH_COORDS = 'Search coords';

	private const CODE_NOT_FOUND = 404;

	private bool $isShortUrl;

	private ?CoordinatesImmutable $placeIdCoords = null;
	private ?CoordinatesImmutable $mapCoords = null;
	private ?CoordinatesImmutable $mapCoordsV2 = null;
	private ?CoordinatesImmutable $searchCoords = null;

	public function __construct(
		private readonly Requestor $requestor,
		private readonly MapyCzApi\MapyCzApi $mapyCzApi,
	) {
	}

	public function validate(): bool
	{
		if (!isset($this->url)) {
			return false;
		}

		$domain = $this->url->getDomain(2);
		if (in_array($domain, ['mapy.com', 'mapy.cz'], true) === false) {
			return false;
		}

		return $this->isShortUrl() || $this->isNormalUrl();
	}

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
		ServicesManager::TAG_GENERATE_LINK_IMAGE,
	];

	public static function getConstants(): array
	{
		return [
			self::TYPE_PANORAMA,
			self::TYPE_PLACE_ID,
			self::TYPE_PLACE_COORDS,
			self::TYPE_MAP,
			self::TYPE_MAP_V2,
			self::TYPE_PHOTO,
			self::TYPE_CUSTOM_POINT,
			self::TYPE_SEARCH_COORDS,
		];
	}

	private function isShortUrl(): bool
	{
		// Mapy.cz short link:
		// https://mapy.cz/s/porumejene
		// https://en.mapy.cz/s/porumejene
		// https://en.mapy.cz/s/3ql7u
		// https://en.mapy.cz/s/faretabotu
		return $this->isShortUrl = (bool)preg_match(
			'/^\/s\/[a-zA-Z0-9]+$/',
			$this->url->getPath(),
		);
	}

	public function isNormalUrl(): bool
	{
		// https://en.mapy.cz/zakladni?x=14.2991869&y=49.0999235&z=16&pano=1&source=firm&id=350556
		// https://mapy.cz/?x=15.278244&y=49.691235&z=15&ma_x=15.278244&ma_y=49.691235&ma_t=Jsem+tady%2C+otev%C5%99i+odkaz&source=coor&id=15.278244%2C49.691235
		// Mapy.cz panorama:
		// https://en.mapy.cz/zakladni?x=14.3139613&y=49.1487367&z=15&pano=1&pid=30158941&yaw=1.813&fov=1.257&pitch=-0.026
		// $parsedUrl = parse_url(urldecode($url)); // @TODO why it is used urldecode?

		$queryId = $this->url->getQueryParameter('id');
		$querySource = $this->url->getQueryParameter('source');
		$queryQ = $this->url->getQueryParameter('q');

		if ($querySource === MapyCzApi\MapyCzApi::SOURCE_COOR && $queryId) { // coordinates in place ID
			$this->placeIdCoords = self::fromLonLatString($queryId);
			if ($this->placeIdCoords !== null) {
				return true;
			}
		}

		if ($queryQ !== null) { // Searching is is using standard "lat,lon" format
			$this->searchCoords = CoordinatesImmutable::fromString($queryQ);
		}

		$this->mapCoords = CoordinatesImmutable::safe(
			$this->url->getQueryParameter('y'),
			$this->url->getQueryParameter('x'),
		);

		$this->mapCoordsV2 = CoordinatesImmutable::safe(
			$this->url->getQueryParameter('ma_y'),
			$this->url->getQueryParameter('ma_x'),
		);

		return (
			$this->searchCoords !== null
			|| $this->mapCoords !== null
			|| $this->mapCoordsV2 !== null // not sure what is this...
			|| ($this->url->getQueryParameter('sourcep') && Strict::isPositiveInt($this->url->getQueryParameter('idp'))) // photo ID
			|| Strict::isPositiveInt($queryId) && $querySource // place ID
			|| Strict::isPositiveInt($this->url->getQueryParameter('pid')) // panorama ID
			|| ($this->url->getQueryParameter('vlastni-body') !== null && $this->url->getQueryParameter('uc')) // custom points
		);
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		// 2021-07-14: Drive link is "kind of" available using planner and on Android device it will open correctly target destination,
		// but empty deparature, so user has to choose current location manually. That is more clicks, than using classic,
		// share link.
		return sprintf('%s/zakladni?y=%2$F&x=%3$f&source=coor&id=%3$f%%2C%2$F', self::LINK, $lat, $lon);
	}

	public static function getShareCollectionLink(BetterLocationCollection $collection): ?string
	{
		$coordsReformatted = array_map(fn($coords) => [$coords->getLon(), $coords->getLat()], $collection->getCoordinates());
		$coordsEncoded = MapyCzApi\JAK\Coords::coordsToString($coordsReformatted);
		return sprintf('%s/zakladni?vlastni-body&uc=%s', self::LINK, $coordsEncoded);
	}

	public function getScreenshotLink(CoordinatesInterface $coordinates, array $options = []): ?string
	{
		// URL Parameters to screenshoter (Mapy.cz website is using it with p=3 and l=0):
		// l=0 hide right panel (can be opened via arrow icon)
		// p=1 disable right panel (can't be opened) and disable bottom left panorama view screenshot
		// p=2 show right panel and (can't be hidden) and disable bottom left panorama view screenshot
		// p=3 disable right panel (can't be opened) and enable bottom left panorama view screenshot
		return 'https://en.mapy.cz/screenshoter?url=' . urlencode(self::getShareLink($coordinates->getLat(), $coordinates->getLon()) . '&p=3&l=0');
	}

	public function process(): void
	{
		if ($this->isShortUrl) {
			$this->processShortUrl();
		}

		$querySource = $this->url->getQueryParameter('source');
		$querySourceP = $this->url->getQueryParameter('sourcep');
		$queryId = $this->url->getQueryParameter('id');

		// URL with Photo ID
		if ($querySourceP !== null && Strict::isPositiveInt($this->url->getQueryParameter('idp'))) {
			try {
				$mapyCzResponse = $this->mapyCzApi->loadPoiDetails($querySourceP, Strict::intval($this->url->getQueryParameter('idp')));
				$betterLocation = new BetterLocation($this->inputUrl, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PHOTO);

				// Query 'fl' parameter contains info, what should be resolution of requested image. If this parameter
				// is removed, original uploaded image is requested including original EXIF metadata.
				// Example of fl parameter: ?fl=res,400,,3
				$highestQualityPhotoUrl = new Url($mapyCzResponse->extend->photo->src);
				$highestQualityPhotoUrl->setQueryParameter('fl', null);

				$prefix = $betterLocation->getPrefixMessage();
				$photoTitle = trim($mapyCzResponse->title);
				$prefix .= sprintf(' <a href="%s">', $highestQualityPhotoUrl);
				if ($photoTitle !== '') {
					$prefix .= htmlspecialchars($photoTitle) . ' ';
				}
				$prefix .= sprintf('%s</a>', Icons::PICTURE);

				$betterLocation->setPrefixMessage($prefix);
				$this->collection->add($betterLocation);
			} catch (MapyCzApiException $exception) {
				if ($exception->getCode() === self::CODE_NOT_FOUND) {
					// not found, swallow
				} else {
					Debugger::log(sprintf('MapyCz Place API response: "%s"', $exception->getMessage()), Debugger::ERROR);
				}
			}
		}

		// URL with Panorama ID
		if (Strict::isPositiveInt($this->url->getQueryParameter('pid'))) {
			try {
				$panoramaId = Strict::intval($this->url->getQueryParameter('pid'));
				$mapyCzResponse = $this->mapyCzApi->loadPanoramaDetails($panoramaId);
				$this->collection->add(new BetterLocation($this->inputUrl, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PANORAMA));
			} catch (MapyCzApiException $exception) {
				if ($exception->getCode() === self::CODE_NOT_FOUND) {
					// not found, swallow
				} else {
					Debugger::log(sprintf('MapyCz Panorama API response: "%s"', $exception->getMessage()), Debugger::ERROR);
				}
			}
		}

		// URL with Place ID
		if ($querySource && Strict::isPositiveInt($queryId)) {
			try {
				$mapyCzResponse = $this->mapyCzApi->loadPoiDetails($querySource, Strict::intval($queryId));
				$betterLocation = new BetterLocation($this->inputUrl, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PLACE_ID);
				$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $this->url, self::NAME, $mapyCzResponse->title));

				$address = $this->addressFromMapyCzPlace($mapyCzResponse);
				if ($address !== null) {
					$betterLocation->setAddress($address);
				}

				$this->collection->add($betterLocation);
			} catch (MapyCzApiException $exception) {
				if ($exception->getCode() === self::CODE_NOT_FOUND) {
					// not found, swallow
				} else {
					Debugger::log(sprintf('MapyCz Place API response: "%s"', $exception->getMessage()), Debugger::ERROR);
				}
			}
		}

		// MapyCZ URL has ID in format of coordinates
		if ($this->placeIdCoords !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->placeIdCoords->lat, $this->placeIdCoords->lon, self::class, self::TYPE_PLACE_COORDS));
		}

		// MapyCZ URL has search parameter 'q' in format of coordinates
		if ($this->collection->isEmpty() && $this->searchCoords !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->searchCoords->lat, $this->searchCoords->lon, self::class, self::TYPE_SEARCH_COORDS));
		}

		// Custom points
		if ($this->url->getQueryParameter('vlastni-body') !== null && $this->url->getQueryParameter('uc')) {
			$this->processCustomPointsUrl();
		}

		// Process map center only if no valid location was detected (place, photo, panorama..)
		if ($this->collection->isEmpty() && $this->mapCoordsV2 !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->mapCoordsV2->lat, $this->mapCoordsV2->lon, self::class, self::TYPE_MAP_V2));
		}

		// Process map center only if no valid location was detected (place, photo, panorama..)
		if ($this->collection->isEmpty() && $this->mapCoords !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->mapCoords->lat, $this->mapCoords->lon, self::class, self::TYPE_MAP));
		}
	}

	private function processShortUrl(): void
	{
		$this->rawUrl = $this->requestor->loadFinalRedirectUrl($this->url);
		$this->url = Strict::url($this->rawUrl);
		if ($this->validate() === false) {
			throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
		}
	}

	private function processCustomPointsUrl(): void
	{
		$encodedCoords = $this->url->getQueryParameter('uc');
		try {
			$decodedCoords = MapyCzApi\JAK\Coords::stringToCoords($encodedCoords);
		} catch (\InvalidArgumentException) {
			return; // URL contains non-valid encoded coordinates, ignore
		}

		$properUrl = $this->getProperCustomPointsUrl();
		$customPlaceTitles = $properUrl->getQueryParameter('ut');
		foreach ($decodedCoords as $key => $decodedCoord) {
			$location = new BetterLocation($this->inputUrl, $decodedCoord['y'], $decodedCoord['x'], self::class, self::TYPE_CUSTOM_POINT);
			$location->setPrefixMessage(sprintf(
				'<a href="%s">%s - %s</a>',
				$this->inputUrl,
				self::NAME,
				$this->getCustomPointTitle($key, $customPlaceTitles[$key] ?? ''),
			));
			$this->collection->add($location);
		}
	}

	/**
	 * Return updated and valid URL from mapy.cz.
	 *
	 * Replacing multiple query parameters with array representation, example:
	 * ...&ut=blabla1&ut=blabla2... ->  ...&ut[]=blabla1&ut[]=blabla2...
	 */
	private function getProperCustomPointsUrl(): UrlImmutable
	{
		$result = $this->rawUrl;
		$result = str_replace('&ut=', '&ut[]=', $result);
		$result = str_replace('&ud=', '&ud[]=', $result);
		return new UrlImmutable($result);
	}

	private function getCustomPointTitle(int $key, string $titleFromUrl): string
	{
		$result = $key + 1 . '.';
		// Default names when creating custom point
		if (!in_array($titleFromUrl, [
			'', // Missing title
			'New  POI', // English has two spaces
			'Neuer Punkt', // Deutsch
			'Nowy punkt', // Polski
			'Nový bod', // Czech and Slovenčina
		], true)) {
			$result .= ' ' . htmlspecialchars($titleFromUrl);
		}
		return $result;
	}

	private function addressFromMapyCzPlace(PlaceType $place): ?Address
	{
		$addressText = trim($place->titleVars?->locationMain1 ?? '');
		if ($addressText === '') {
			return null;
		}

		$country = $this->countryFromMapyCzPlace($place);
		return new Address($addressText, $country);
	}

	public function countryFromMapyCzPlace(PlaceType $place): ?Country
	{
		$countryIsoCode = $place->extend?->address?->country_iso ?? null;
		if ($countryIsoCode === null) {
			return null;
		}

		try {
			return Country::fromNumericCode($countryIsoCode);
		} catch (\Throwable) {
			// Example
			// https://en.mapy.cz/zakladni?x=-67.5159386&y=-45.8711989&z=15&source=osm&id=17164289
			// throws 'League\ISO3166\Exception\DomainException : Not a valid numeric key: 32'
			return null;
		}
	}

	private static function fromLonLatString(string $input): ?CoordinatesImmutable
	{
		$parsed = explode(',', $input);
		if (count($parsed) !== 2) {
			return null;
		}
		[$lon, $lat] = $parsed; // Mapy.cz is using different order
		return CoordinatesImmutable::safe($lat, $lon);
	}
}
