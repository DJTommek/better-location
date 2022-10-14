<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;

final class WazeService extends AbstractService
{
	const ID = 3;
	const NAME = 'Waze';

	const LINK = 'https://www.waze.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		$link = sprintf(self::LINK . '/ul?ll=%1$F,%2$F', $lat, $lon);
		if ($drive) {
			$link .= '&navigate=yes';
		}
		return $link;
	}

	public function isValid(): bool
	{
		return $this->isShortUrl() || $this->isNormalUrl();
	}

	private function isShortUrl(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'waze.com' &&
			preg_match('/^\/ul\/h([a-z0-9A-Z]+)$/', $this->url->getPath(), $matches)
		) {
			$this->data->isShortUrl = true;
			$this->data->shortUrlCode = $matches[1];
			return true;
		}
		return false;
	}

	private function isNormalUrl(): bool
	{
		if ($this->url && $this->url->getDomain(2) === 'waze.com') {

			// Example: https://www.waze.com/ul?ll=50.06300713%2C14.43964005
			$this->data->ll = Coordinates::fromString($this->url->getQueryParameter('ll') ?? '');

			// Example: https://www.waze.com/cs/livemap/directions?latlng=50.063007132127616%2C14.439640045166016
			$this->data->latLng = Coordinates::fromString($this->url->getQueryParameter('latlng') ?? '');

			// Example: https://www.waze.com/cs/livemap/directions?to=ll.50.07734439%2C14.43475842
			if ($this->url->getQueryParameter('to')) {
				$param = ltrim($this->url->getQueryParameter('to'), 'l.');
				$this->data->to = Coordinates::fromString($param);
			}

			// Example: https://www.waze.com/live-map/directions?from=ll.50.093652%2C14.412417
			if ($this->url->getQueryParameter('from')) {
				$param = ltrim($this->url->getQueryParameter('from'), 'l.');
				$this->data->from = Coordinates::fromString($param);
			}

			// This URL is from Wikipedia's Geohack (https://geohack.toolforge.org/geohack.php?params=050.093652_N_0014.412417_E)
			// Example: https://www.waze.com/livemap/?zoom=11&lat=50.093652&lon=14.412417
			$this->data->queryLatLon = Coordinates::safe(
				$this->url->getQueryParameter('lat'),
				$this->url->getQueryParameter('lon')
			);
		}

		return ($this->data->ll || $this->data->latLng || $this->data->to || $this->data->from || $this->data->queryLatLon);
	}

	public function process(): void
	{
		if ($this->data->isShortUrl ?? false) {
			$this->url = Strict::url($this->getRedirectUrl());
			if ($this->isValid() === false) {
				throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
			}
		}

		if ($this->data->ll ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->ll->getLat(), $this->data->ll->getLon(), self::class));
		}
		if ($this->data->latLng ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->latLng->getLat(), $this->data->latLng->getLon(), self::class));
		}
		if ($this->data->to ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->to->getLat(), $this->data->to->getLon(), self::class));
		}
		if ($this->data->from ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->from->getLat(), $this->data->from->getLon(), self::class));
		}
		if ($this->data->queryLatLon ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->queryLatLon->getLat(), $this->data->queryLatLon->getLon(), self::class));
		}
	}

	/**
	 * Optimize number of requests by changing input URL to third redirect
	 *
	 * https://waze.com/ul/hu2fhzy57j
	 * -> https://www.waze.com/ul/hu2fhzy57j
	 * -> https://www.waze.com/live-map?h=u2fhzy57j
	 * -> /live-map/?to=ll.50.087206%2C14.407775 (https://www.waze.com/live-map/?to=ll.50.087206%2C14.407775)
	 * -> /live-map/directions?to=ll.50.087206%2C14.407775 (https://www.waze.com/live-map/directions?to=ll.50.087206%2C14.407775)
	 */
	private function getRedirectUrl(): string
	{
		$urlToRequest = self::LINK . '/live-map?h=' . $this->data->shortUrlCode;
		return self::LINK . MiniCurl::loadRedirectUrl($urlToRequest);
	}
}
