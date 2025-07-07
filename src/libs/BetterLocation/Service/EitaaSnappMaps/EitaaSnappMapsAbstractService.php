<?php declare(strict_types=1);

namespace App\BetterLocation\Service\EitaaSnappMaps;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;

abstract class EitaaSnappMapsAbstractService extends AbstractService
{
	private ?CoordinatesInterface $mapCenterCoords = null;

	protected abstract static function getDomain(): string;

	public function validate(): bool
	{
		return (
			$this->url
			&& $this->url->getDomain(0) === static::getDomain()
			&& $this->isMapUrl()
		);
	}

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	private function isMapUrl(): bool
	{
		// https://map.eitaa.com/#13.29/34.64018/50.90336/5.1/46
		// https://tile.snappmaps.ir/styles/en-snapp-style/#13.29/34.64018/50.90336/5.1/46
		// https://tile.snappmaps.ir/#13.29/34.64018/50.90336/5.1/46
		[$zoom, $lat, $lon, $bearing, $tilt] = array_pad(explode('/', $this->url->getFragment()), 5, '');
		$this->mapCenterCoords = CoordinatesImmutable::safe($lat, $lon);
		return $this->mapCenterCoords !== null;
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		return sprintf(
			'https://%s/#%s/%F/%F',
			static::getDomain(),
			'13.0', // default zoom
			$lat,
			$lon,
		);
	}

	public function process(): void
	{
		if ($this->mapCenterCoords !== null) {
			$location = new BetterLocation($this->input, $this->mapCenterCoords->getLat(), $this->mapCenterCoords->getLon(), static::class);
			$this->collection->add($location);
		}
	}
}
