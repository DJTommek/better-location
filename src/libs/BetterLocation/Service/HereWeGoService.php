<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\Utils\Requestor;
use App\Utils\Strict;
use DJTommek\Coordinates\Coordinates;
use Nette\Http\Url;

final class HereWeGoService extends AbstractService
{
	const ID = 5;
	const NAME = 'HERE WeGo';
	const NAME_SHORT = 'HERE';

	const LINK = 'https://wego.here.com';
	const LINK_SHARE = 'https://share.here.com';

	const RE_COORDS_IN_MAP = '/(?:[,:\/]|^)(-?[0-9]{1,2}\.[0-9]{1,})[,\/](-?[0-9]{1,3}\.[0-9]{1,})(?:[,\/]|$)/';

	const TYPE_MAP = 'Map center';
	const TYPE_PLACE_COORDS = 'Place coords';
	const TYPE_PLACE_SHARE = 'Place share';
	const TYPE_PLACE_ORIGINAL_ID = 'Place';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_PLACE_ORIGINAL_ID,
			self::TYPE_PLACE_SHARE,
			self::TYPE_PLACE_COORDS,
			self::TYPE_MAP,
		];
	}

	/** @see https://developer.here.com/documentation/deeplink-web/dev_guide/topics/key-concepts.html */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-route.html
			return self::LINK_SHARE . sprintf('/r/%1$F,%2$F', $lat, $lon);
		} else { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-location.html
			return self::LINK_SHARE . sprintf('/l/%1$F,%2$F?p=yes', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		return $this->isShortUrl() || $this->isNormalUrl();
	}

	public function isShortUrl(): bool
	{
		return $this->data->isShortUrl = $this->url?->getDomain(0) === 'her.is';
	}

	public function isNormalUrl(): bool
	{
		return $this->url && in_array($this->url->getDomain(0), ['share.here.com', 'wego.here.com']);
	}

	public function process(): void
	{
		if ($this->isShortUrl()) {
			$this->processShortShareUrl();
		}
		if ($this->isNormalUrl()) {
			$this->processNormalUrl();
		}
	}

	private function placeLocationFromUrl(Url $url): ?Coordinates
	{
		$placeData = self::extractPlaceInfo($url);
		return Coordinates::safe(
			$placeData['lat'] ?? null,
			$placeData['lon'] ?? null,
		);
	}

	/**
	 * Extract information from URL
	 *
	 * @return array<string, string>
	 * @example
	 *      Original URL: 'https://wego.here.com/saint-helena/sandy-bay/city-town-village/sandy-bay--loc-dmVyc2lvbj0xO3RpdGxlPVNhbmR5K0JheTtsYXQ9LTE1Ljk3ODE2O2xvbj0tNS43MTIwNTtjaXR5PVNhbmR5K0JheTtjb3VudHJ5PVNITjtjb3VudHk9U2FuZHkrQmF5O2NhdGVnb3J5SWQ9Y2l0eS10b3duLXZpbGxhZ2U7c291cmNlU3lzdGVtPWludGVybmFs?map=-15.99429,-5.75681,15,normal&msg=Sandy%20Bay'
	 *      Part of URL to decode: 'dmVyc2lvbj0xO3RpdGxlPVNhbmR5K0JheTtsYXQ9LTE1Ljk3ODE2O2xvbj0tNS43MTIwNTtjaXR5PVNhbmR5K0JheTtjb3VudHJ5PVNITjtjb3VudHk9U2FuZHkrQmF5O2NhdGVnb3J5SWQ9Y2l0eS10b3duLXZpbGxhZ2U7c291cmNlU3lzdGVtPWludGVybmFs'
	 *      Base 64 decoded: 'version=1;title=Sandy+Bay;lat=-15.97816;lon=-5.71205;city=Sandy+Bay;country=SHN;county=Sandy+Bay;categoryId=city-town-village;sourceSystem=internal'
	 *      Coordinates of place: -15.97816,-5.71205
	 *
	 * @internal Public for tests
	 */
	public static function extractPlaceInfo(Url $url): ?array
	{
		$base64regexChars = 'a-zA-Z0-9+=';
		$urlPath = urldecode($url->getPath());
		if (
			!preg_match('/--loc-([' . $base64regexChars . ']+)/', $urlPath, $matches)
			&& !preg_match('/\/p\/s-([' . $base64regexChars . ']+)/', $urlPath, $matches)
		) {
			return null;
		}

		$placeDataRaw = base64_decode($matches[1]);
		$placeData = [];
		foreach (explode(';', $placeDataRaw) as $dataRaw) {
			[$key, $value] = explode('=', $dataRaw, 2);
			$placeData[$key] = $value;
		}

		return $placeData;
	}

	/**
	 * Process share URL which after two redirects contain map coordinates in URL which are the same as shared place coordinates.
	 * This allow skip doing actual request and downloading full page, just reading HTTP headers (much more resource friendly)
	 * Example (see test for more examples):
	 * -> https://her.is/3lZVXD3
	 * -> https://share.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?ref=here_com
	 * -> https://wego.here.com/p/s-Yz1wb3N0YWwtYXJlYTtsYXQ9NTAuMTA5NTc7bG9uPTE0LjQ0MTIyO249UHJhaGErNztoPTc1NWM3OQ?map=50.10957%2C14.44122%2C15%2Cnormal&ref=here_com
	 * = 50.10957, 14.44122
	 */
	private function processShortShareUrl(): void
	{
		$this->url = Strict::url($this->requestor->loadFinalRedirectUrl($this->url));
		if ($this->validate() === false || $this->data->isShortUrl === true) {
			throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
		}
	}

	public function processNormalUrl(): void
	{
		$messageInUrl = $this->url->getQueryParameter('msg') ? htmlspecialchars($this->url->getQueryParameter('msg')) : null;

		$placeCoordsFromUrl = $this->placeLocationFromUrl($this->url);
		if ($placeCoordsFromUrl) {
			$location = new BetterLocation($this->inputUrl, $placeCoordsFromUrl->getLat(), $placeCoordsFromUrl->getLon(), self::class, self::TYPE_PLACE_ORIGINAL_ID);
			$this->collection->add($location);
		}

		if (preg_match(self::RE_COORDS_IN_MAP, $this->url->getPath(), $matches)) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				$location = new BetterLocation($this->inputUrl, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class, self::TYPE_PLACE_COORDS);
				if ($messageInUrl) {
					$location->setPrefixMessage($location->getPrefixMessage() . ' ' . $messageInUrl);
				}
				$this->collection->add($location);
			}
		}
		if (preg_match('/^(-?[0-9]{1,2}\.[0-9]{1,}),(-?[0-9]{1,3}\.[0-9]{1,}),/', $this->url->getQueryParameter('map') ?? '', $matches)) {
			$type = ($this->data->isShortUrl ?? false) ? self::TYPE_PLACE_SHARE : self::TYPE_MAP;
			$this->collection->add(new BetterLocation($this->inputUrl, floatval($matches[1]), floatval($matches[2]), self::class, $type));
		}
	}
}
