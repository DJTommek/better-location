<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Nette\Http\UrlImmutable;
use Nette\Utils\Strings;

final class OpenStreetMapService extends AbstractServiceNew
{
	const NAME = 'OSM';

	const LINK = 'https://www.openstreetmap.org';

	const TYPE_MAP = 'Map';
	const TYPE_POINT = 'Point';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			return self::LINK . sprintf('/directions?from=&to=%1$f,%2$f', $lat, $lon);
		} else {
			return self::LINK . sprintf('/search?whereami=1&query=%1$f,%2$f&mlat=%1$f&mlon=%2$f#map=17/%1$f/%2$f', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		$result = false;
		if ($this->url->getDomain(2) === 'openstreetmap.org') {
			if (Coordinates::isLat($this->url->getQueryParameter('mlat')) && Coordinates::isLon($this->url->getQueryParameter('mlon'))) {
				$this->data->pointCoord = true;
				$this->data->pointCoordLat = Strict::floatval($this->url->getQueryParameter('mlat'));
				$this->data->pointCoordLon = Strict::floatval($this->url->getQueryParameter('mlon'));
				$result = true;
			}
			if ($this->url->getFragment()) {
				parse_str($this->url->getFragment(), $fragments);
				if (isset($fragments['map'])) {
					$coords = explode('/', $fragments['map']);
					if (count($coords) >= 3 && Coordinates::isLat($coords[1]) && Coordinates::isLon($coords[2])) {
						$this->data->mapCoord = true;
						$this->data->mapCoordLat = Strict::floatval($coords[1]);
						$this->data->mapCoordLon = Strict::floatval($coords[2]);
						$result = true;
					}
				}
			}
		} else if ($this->url->getDomain(0) === 'osm.org' && Strings::startsWith($this->url->getPath(), '/go/')) {
			$this->data->isShortUrl = true;
			$result = true;
		}
		return $result;
	}

	public function process(): void
	{
		if ($this->data->isShortUrl ?? false) {
			$urlToRequest = $this->url->withHost('www.openstreetmap.org');
			$this->url = new UrlImmutable(MiniCurl::loadRedirectUrl($urlToRequest->getAbsoluteUrl()));
			if ($this->isValid() === false) {
				throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url->getAbsoluteUrl(), $this->inputUrl->getAbsoluteUrl()));
			}
		}

		if ($this->data->pointCoord ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl->getAbsoluteUrl(), $this->data->pointCoordLat, $this->data->pointCoordLon, self::class, self::TYPE_POINT));
		}
		if ($this->data->mapCoord ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl->getAbsoluteUrl(), $this->data->mapCoordLat, $this->data->mapCoordLon, self::class, self::TYPE_MAP));
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_POINT,
			self::TYPE_MAP,
		];
	}
}
