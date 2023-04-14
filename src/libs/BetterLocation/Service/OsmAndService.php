<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ServicesManager;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Utils\Arrays;

final class OsmAndService extends AbstractService
{
	const ID = 15;
	const NAME = 'OsmAnd';

	const LINK = 'https://osmand.net';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	const TYPE_GO = 'Go';
	const TYPE_PIN = 'Pin';
	const TYPE_MAP_CENTER = 'Map center';

	public static function getConstants(): array
	{
		return [
			self::TYPE_PIN,
			self::TYPE_GO,
			self::TYPE_MAP_CENTER,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		return self::LINK . sprintf('/go.html?lat=%1$F&lon=%2$F', $lat, $lon);
	}

	public function isValid(): bool
	{
		$result = false;
		if ($this->url && $this->url->getDomain(2) === 'osmand.net') {
			if (Arrays::contains(['/go', '/go.html'], $this->url->getPath())) {
				$this->data->goCoords = Coordinates::safe(
					$this->url->getQueryParameter('lat'),
					$this->url->getQueryParameter('lon'),
				);
				if ($this->data->goCoords !== null) {
					$result = true;
				}
			}

			if ($this->url->getPath() === '/map') {
				$pin = $this->url->getQueryParameter('pin');
				$this->data->pinCoords = Coordinates::fromString($pin ?? '');
				if ($this->data->pinCoords !== null) {
					$result = true;
				}

				$this->data->mapCenter = $this->getCoordsFromUrlFragment($this->url->getFragment());
				if ($this->data->mapCenter !== null) {
					$result = true;
				}
			}
		}

		return $result;
	}

	public function process(): void
	{
		$coords = $this->data->goCoords ?? null;
		if ($coords instanceof CoordinatesInterface) {
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_GO);
			$this->collection->add($location);
		}

		$coords = $this->data->pinCoords ?? null;
		if ($coords instanceof CoordinatesInterface) {
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_PIN);
			$this->collection->add($location);
		}

		if ($this->collection->isEmpty()) {
			$coords = $this->data->mapCenter ?? null;
			if ($coords instanceof CoordinatesInterface) {
				$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_MAP_CENTER);
				$this->collection->add($location);
			}
		}
	}

	private function getCoordsFromUrlFragment($fragment): ?CoordinatesInterface
	{
		$regex = sprintf('/^\d+\/(%s)\/(%s)$/', Coordinates::RE_BASIC_LAT, Coordinates::RE_BASIC_LON);
		if (preg_match($regex, $fragment, $matches)) {
			return Coordinates::safe($matches[1], $matches[2]);
		}
		return null;
	}
}
