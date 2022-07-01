<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Nette\Utils\Arrays;

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
			return self::LINK_SHARE . sprintf('/r/%1$f,%2$f', $lat, $lon);
		} else { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-location.html
			return self::LINK_SHARE . sprintf('/l/%1$f,%2$f?p=yes', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		return $this->isShortUrl() || $this->isNormalUrl();
	}

	public function isShortUrl(): bool
	{
		if ($this->url && Arrays::contains(['her.is'], $this->url->getDomain(0))) {
			$this->data->isShortUrl = true;
			return true;
		}
		return false;
	}

	public function isNormalUrl(): bool
	{
		return $this->url && Arrays::contains([
				'share.here.com',
				'wego.here.com',
			], $this->url->getDomain(0));
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

	public function processNormalUrl(): void
	{
		$messageInUrl = $this->url->getQueryParameter('msg') ? htmlspecialchars($this->url->getQueryParameter('msg')) : null;

		if (preg_match('/--loc-[a-zA-Z0-9]+/', $this->url->getPath())) {
			$locationData = self::requestByLoc($this->url->getAbsoluteUrl());
			// @TODO use property "name" or set of properties in "address.*" to better describe current location
			$location = new BetterLocation($this->inputUrl, $locationData->geo->latitude, $locationData->geo->longitude, self::class, self::TYPE_PLACE_ORIGINAL_ID);
			if ($messageInUrl) {
				$location->setPrefixMessage($location->getPrefixMessage() . ' ' . $messageInUrl);
			}
			$this->collection->add($location);
		}

		// Short links always center map to point so there is no need to load page to get information about point
		if (($this->data->isShortUrl ?? false) === false && preg_match('/^\/p\/s-[a-zA-Z0-9]+$/', $this->url->getPath())) { // from short links
			// need to replace from "share" subdomain, otherwise there would be another redirect
			$locationData = self::requestByLoc(str_replace('https://share.here.com/', 'https://wego.here.com/', $this->url->getAbsoluteUrl()));
			// @TODO use property "name" or set of properties in "address.*" to better describe current location
			$this->collection->add(new BetterLocation($this->inputUrl, $locationData->geo->latitude, $locationData->geo->longitude, self::class, self::TYPE_PLACE_SHARE));
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

	private static function requestByLoc(string $url): \stdClass
	{
		$response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_HERE_WE_GO_LOC)->run()->getBody();
		// @TODO probably could be solved somehow better. Needs more testing
		preg_match('/<script type="application\/ld\+json">(.+?)<\/script>/s', $response, $matches);
		return json_decode($matches[1]);
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
		$this->url = Strict::url(MiniCurl::loadRedirectUrl($this->url->getAbsoluteUrl()));
		if ($this->url->getDomain(0) !== 'share.here.com') {
			throw new InvalidLocationException(sprintf('Unexpected first redirect URL "%s".', $this->url));
		}
		$this->url = Strict::url(MiniCurl::loadRedirectUrl($this->url->getAbsoluteUrl()));
		if ($this->url->getDomain(0) !== 'wego.here.com') {
			throw new InvalidLocationException(sprintf('Unexpected second redirect URL "%s".', $this->url));
		}
	}
}
