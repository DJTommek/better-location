<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;
use Nette\Utils\Arrays;
use Nette\Utils\Strings;

final class OpenStreetMapService extends AbstractService
{
	const ID = 7;
	const NAME = 'OpenStreetMap';
	const NAME_SHORT = 'OSM';

	const LINK = 'https://www.openstreetmap.org';

	const TYPE_MAP = 'Map';
	const TYPE_POINT = 'Point';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			return self::LINK . sprintf('/directions?from=&to=%1$F,%2$F', $lat, $lon);
		} else {
			return self::LINK . sprintf('/search?whereami=1&query=%1$F,%2$F&mlat=%1$F&mlon=%2$F#map=17/%1$F/%2$F', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		$result = false;
		if ($this->url && Arrays::contains(['openstreetmap.org', 'osm.org'], $this->url->getDomain(2))) {
			if (Strings::startsWith($this->url->getPath(), '/go/')) {
				$this->data->isShortUrl = true;
				$result = true;
			} else {
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
			}
		}
		return $result;
	}

	public function process(): void
	{
		if ($this->data->isShortUrl ?? false) {
			$this->url->setHost('www.openstreetmap.org');
			$this->url = Strict::url(MiniCurl::loadRedirectUrl($this->url->getAbsoluteUrl()));
			if ($this->isValid() === false) {
				throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
			}
		}

		if ($this->data->pointCoord ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->pointCoordLat, $this->data->pointCoordLon, self::class, self::TYPE_POINT));
		}
		if ($this->data->mapCoord ?? false) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->mapCoordLat, $this->data->mapCoordLon, self::class, self::TYPE_MAP));
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
