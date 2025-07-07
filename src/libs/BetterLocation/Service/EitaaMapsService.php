<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;

final class EitaaMapsService extends AbstractService
{
	public const ID = 60;
	public const NAME = 'Eitaa Maps';
	public const LINK = 'https://map.eitaa.com';

	private ?CoordinatesInterface $mapCenterCoords = null;

	public function validate(): bool
	{
		return (
			$this->url
			&& $this->url->getDomain(0) === 'map.eitaa.com'
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
		[$zoom, $lat, $lon, $bearing, $tilt] = explode('/', $this->url->getFragment());
		$this->mapCenterCoords = CoordinatesImmutable::safe($lat, $lon);
		return $this->mapCenterCoords !== null;
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		$params = [
			'13.0', // default zoom
			sprintf('%F', $lat),
			sprintf('%F', $lon),
		];
		return self::LINK . '/#' . implode('/', $params);
	}

	public function process(): void
	{
		if ($this->mapCenterCoords !== null) {
			$location = new BetterLocation($this->input, $this->mapCenterCoords->getLat(), $this->mapCenterCoords->getLon(), self::class);
			$this->collection->add($location);
		}
	}
}
