<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;

final class WazeService extends AbstractService
{
	const ID = 3;
	const NAME = 'Waze';

	const LINK = 'https://www.waze.com';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		$link = sprintf(self::LINK . '/ul?ll=%1$f,%2$f', $lat, $lon);
		if ($drive) {
			$link .= '&navigate=yes';
		}
		return $link;
	}

	public function isValid(): bool
	{
		return $this->isShortUrl() || $this->isNormalUrl();
	}

	public function isShortUrl(): bool
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

	public function isNormalUrl(): bool
	{
		$result = false;
		if ($this->url && $this->url->getDomain(2) === 'waze.com') {
			if ($coords = Coordinates::getLatLon($this->url->getQueryParameter('ll') ?? '')) {
				$this->data->ll = true;
				$this->data->llLat = $coords[0];
				$this->data->llLon = $coords[1];
				$result = true;
			}
			if ($coords = Coordinates::getLatLon($this->url->getQueryParameter('latlng') ?? '')) {
				$this->data->latLng = true;
				$this->data->latLngLat = $coords[0];
				$this->data->latLngLon = $coords[1];
				$result = true;
			}
			if ($this->url->getQueryParameter('to')) {
				$param = ltrim($this->url->getQueryParameter('to'), 'l.');
				if ($coords = Coordinates::getLatLon($param)) {
					$this->data->to = true;
					$this->data->toLat = $coords[0];
					$this->data->toLon = $coords[1];
					$result = true;
				}
			}
			if ($this->url->getQueryParameter('from')) {
				$param = ltrim($this->url->getQueryParameter('from'), 'l.');
				if ($coords = Coordinates::getLatLon($param)) {
					$this->data->from = true;
					$this->data->fromLat = $coords[0];
					$this->data->fromLon = $coords[1];
					$result = true;
				}
			}
			// This URL is from Wikipedia's Geohack, eg. https://geohack.toolforge.org/geohack.php?params=050.093652_N_0014.412417_E
			if (Coordinates::isLat($this->url->getQueryParameter('lat')) && Coordinates::isLon($this->url->getQueryParameter('lon'))) {
				$this->data->queryLatLon = true;
				$this->data->queryLatLonLat = Strict::floatval($this->url->getQueryParameter('lat'));
				$this->data->queryLatLonLon = Strict::floatval($this->url->getQueryParameter('lon'));
				$result = true;
			}
		}
		return $result;
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
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->llLat, $this->data->llLon, self::class));
		}
		if ($this->data->latLng ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->latLngLat, $this->data->latLngLon, self::class));
		}
		if ($this->data->to ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->toLat, $this->data->toLon, self::class));
		}
		if ($this->data->from ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->fromLat, $this->data->fromLon, self::class));
		}
		if ($this->data->queryLatLon ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->queryLatLonLat, $this->data->queryLatLonLon, self::class));
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
