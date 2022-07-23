<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\Icons;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use DJTommek\MapyCzApi;
use DJTommek\MapyCzApi\MapyCzApiException;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;

final class MapyCzService extends AbstractService
{
	const ID = 8;
	const NAME = 'Mapy.cz';
	const LINK = 'https://mapy.cz';

	const TYPE_UNKNOWN = 'unknown';
	const TYPE_MAP = 'Map center';
	const TYPE_PLACE_ID = 'Place';
	const TYPE_PLACE_COORDS = 'Place coords';
	const TYPE_PANORAMA = 'Panorama';
	const TYPE_PHOTO = 'Photo';
	const TYPE_CUSTOM_POINT = 'Custom point';

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'mapy.cz' &&
			(
				$this->isShortUrl() ||
				$this->isNormalUrl()
			)
		);
	}

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
		ServicesManager::TAG_GENERATE_LINK_IMAGE,
	];

	private function isShortUrl()
	{
		// Mapy.cz short link:
		// https://mapy.cz/s/porumejene
		// https://en.mapy.cz/s/porumejene
		// https://en.mapy.cz/s/3ql7u
		// https://en.mapy.cz/s/faretabotu
		return $this->data->isShortUrl = (preg_match('/^\/s\/[a-zA-Z0-9]+$/', $this->url->getPath()));
	}

	public function isNormalUrl(): bool
	{
		// https://en.mapy.cz/zakladni?x=14.2991869&y=49.0999235&z=16&pano=1&source=firm&id=350556
		// https://mapy.cz/?x=15.278244&y=49.691235&z=15&ma_x=15.278244&ma_y=49.691235&ma_t=Jsem+tady%2C+otev%C5%99i+odkaz&source=coor&id=15.278244%2C49.691235
		// Mapy.cz panorama:
		// https://en.mapy.cz/zakladni?x=14.3139613&y=49.1487367&z=15&pano=1&pid=30158941&yaw=1.813&fov=1.257&pitch=-0.026
//		$parsedUrl = parse_url(urldecode($url)); // @TODO why it is used urldecode?

		if ($this->url->getQueryParameter('source') === 'coor' && $this->url->getQueryParameter('id')) { // coordinates in place ID
			$coords = explode(',', $this->url->getQueryParameter('id'));
			if (count($coords) === 2 && Coordinates::isLat($coords[1]) && Coordinates::isLon($coords[0])) {
				$this->data->placeIdCoord = true;
				$this->data->placeIdCoordLat = Strict::floatval($coords[1]);
				$this->data->placeIdCoordLon = Strict::floatval($coords[0]);
				return true;
			}
		}

		return (
			Coordinates::isLat($this->url->getQueryParameter('x')) && Coordinates::isLon($this->url->getQueryParameter('y')) || // map position
			($this->url->getQueryParameter('sourcep') && Strict::isPositiveInt($this->url->getQueryParameter('idp'))) || // photo ID
			Strict::isPositiveInt($this->url->getQueryParameter('id')) && $this->url->getQueryParameter('source') || // place ID
			Strict::isPositiveInt($this->url->getQueryParameter('pid')) || // panorama ID
			($this->url->getQueryParameter('vlastni-body') !== null && $this->url->getQueryParameter('uc')) || // custom points
			Coordinates::isLat($this->url->getQueryParameter('ma_x')) && Coordinates::isLon($this->url->getQueryParameter('ma_y')) // not sure what is this...
		);
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_PANORAMA,
			self::TYPE_PLACE_ID,
			self::TYPE_PLACE_COORDS,
			self::TYPE_MAP,
			self::TYPE_UNKNOWN,
			self::TYPE_PHOTO,
			self::TYPE_CUSTOM_POINT,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		// 2021-07-14: Drive link is "kind of" available using planner and on Android device it will open correctly target destination,
		// but empty deparature, so user has to choose current location manually. That is more clicks, than using classic,
		// share link.
		return sprintf('%s/zakladni?y=%2$f&x=%3$f&source=coor&id=%3$f%%2C%2$f', self::LINK, $lat, $lon);
	}

	public static function getCollectionLink(BetterLocationCollection $collection): string
	{
		return sprintf('%s/?query=%s', self::LINK, implode(';', $collection->getKeys()));
	}

	public static function getScreenshotLink(float $lat, float $lon, array $options = []): ?string
	{
		// URL Parameters to screenshoter (Mapy.cz website is using it with p=3 and l=0):
		// l=0 hide right panel (can be opened via arrow icon)
		// p=1 disable right panel (can't be opened) and disable bottom left panorama view screenshot
		// p=2 show right panel and (can't be hidden) and disable bottom left panorama view screenshot
		// p=3 disable right panel (can't be opened) and enable bottom left panorama view screenshot
		return 'https://en.mapy.cz/screenshoter?url=' . urlencode(self::getShareLink($lat, $lon) . '&p=3&l=0');
	}

	public function process(): void
	{
		if ($this->data->isShortUrl) {
			$this->processShortUrl();
		}
		$mapyCzApi = new MapyCzApi\MapyCzApi();

		// URL with Photo ID
		if ($this->url->getQueryParameter('sourcep') && Strict::isPositiveInt($this->url->getQueryParameter('idp'))) {
			try {
				$mapyCzResponse = $mapyCzApi->loadPoiDetails($this->url->getQueryParameter('sourcep'), Strict::intval($this->url->getQueryParameter('idp')));
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
					$prefix .= htmlentities($photoTitle) . ' ';
				}
				$prefix .= sprintf('%s</a>', Icons::PICTURE);

				$betterLocation->setPrefixMessage($prefix);
				$this->collection->add($betterLocation);
			} catch (MapyCzApiException $exception) {
				Debugger::log(sprintf('MapyCz Place API response: "%s"', $exception->getMessage()), Debugger::ERROR);
			}
		}

		// URL with Panorama ID
		if (Strict::isPositiveInt($this->url->getQueryParameter('pid'))) {
			try {
				$mapyCzResponse = $mapyCzApi->loadPanoramaDetails(Strict::intval($this->url->getQueryParameter('pid')));
				$this->collection->add(new BetterLocation($this->inputUrl, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PANORAMA));
			} catch (MapyCzApiException $exception) {
				Debugger::log(sprintf('MapyCz Panorama API response: "%s"', $exception->getMessage()), Debugger::ERROR);
			}
		}

		// URL with Place ID
		if ($this->url->getQueryParameter('source') && Strict::isPositiveInt($this->url->getQueryParameter('id'))) {
			try {
				$mapyCzResponse = $mapyCzApi->loadPoiDetails($this->url->getQueryParameter('source'), Strict::intval($this->url->getQueryParameter('id')));
				$betterLocation = new BetterLocation($this->inputUrl, $mapyCzResponse->getLat(), $mapyCzResponse->getLon(), self::class, self::TYPE_PLACE_ID);
				$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $this->url, self::NAME, $mapyCzResponse->title));
				if ($mapyCzResponse->titleVars->locationMain1) {
					$betterLocation->setAddress($mapyCzResponse->titleVars->locationMain1);
				}
				$this->collection->add($betterLocation);
			} catch (MapyCzApiException $exception) {
				Debugger::log(sprintf('MapyCz Place API response: "%s"', $exception->getMessage()), Debugger::ERROR);
			}
		}

		// MapyCZ URL has ID in format of coordinates
		if (($this->data->placeIdCoord ?? false) === true) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->placeIdCoordLat, $this->data->placeIdCoordLon, self::class, self::TYPE_PLACE_COORDS));
		}

		// Custom points
		if ($this->url->getQueryParameter('vlastni-body') !== null && $this->url->getQueryParameter('uc')) {
			$this->processCustomPointsUrl();
		}

		// @EXPERIMENTAL Process map center only if no valid location was detected (place, photo, panorama..)
		if ($this->collection->isEmpty()) {
			if (Strict::isFloat($this->url->getQueryParameter('ma_x')) && Strict::isFloat($this->url->getQueryParameter('ma_y'))) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($this->url->getQueryParameter('ma_y')), Strict::floatval($this->url->getQueryParameter('ma_x')), self::class, self::TYPE_UNKNOWN));
			}
		}

		// @EXPERIMENTAL Process map center only if no valid location was detected (place, photo, panorama..)
		if ($this->collection->isEmpty()) {
			if (Coordinates::isLon($this->url->getQueryParameter('x')) && Coordinates::isLat($this->url->getQueryParameter('y'))) {
				$this->collection->add(new BetterLocation($this->inputUrl, Strict::floatval($this->url->getQueryParameter('y')), Strict::floatval($this->url->getQueryParameter('x')), self::class, self::TYPE_MAP));
			}
		}
	}

	private function processShortUrl()
	{
		$this->rawUrl = MiniCurl::loadRedirectUrl($this->url->getAbsoluteUrl());
		$this->url = Strict::url($this->rawUrl);
		if ($this->isValid() === false) {
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
			$result .= ' ' . htmlentities($titleFromUrl);
		}
		return $result;
	}
}
