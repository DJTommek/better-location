<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\Coordinates;

final class NeshanOrgService extends AbstractService
{
	public const ID = 44;
	public const NAME = 'Neshan.org';
	public const LINK = 'https://neshan.org/maps';

	public const TYPE_MAP = 'Map center';
	public const TYPE_PLACE_ID = 'Place';

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'neshan.org' &&
			str_starts_with($this->url->getPath(), '/maps/') &&
			(
				$this->isMapUrl() ||
				$this->isPlaceUrl()
			)
		);
	}

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	private function isMapUrl(): bool
	{
		$valid = false;

		// https://neshan.org/maps/@30.703832,34.190355,6.9z,0.0p
		// https://neshan.org/maps/30.703832,34.190355,6.9z,0.0p
		if (preg_match('/@?(' . Coordinates::RE_BASIC . ')/', $this->url->getPath(), $matches)) {
			$this->data->mapCoords = Coordinates::fromString($matches[1]);
			$valid = true;
		}

		return $valid;
	}

	private function isPlaceUrl(): bool
	{
		$valid = false;

		// https://neshan.org/maps/places/73df0cd31a0f9bbc538885c993583e94
		// https://neshan.org/maps/@35.825205,50.939427,15.0z,0.0p/places/73df0cd31a0f9bbc538885c993583e94
		// https://neshan.org/maps/places/73df0cd31a0f9bbc538885c993583e94/%DA%A9%D8%B1%D8%AC+%D8%A8%D8%A7%D8%B2%D8%A7%D8%B1%DA%86%D9%87+%D8%A8%D8%B2%D8%B1%DA%AF+%DA%AF%D9%84%D8%B4%D9%87%D8%B1
		// @TODO add support

		return $valid;
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP,
			self::TYPE_PLACE_ID,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$params = [
				sprintf('%F', $lat),
				sprintf('%F', $lon),
				'11.0z', // default zoom
				'0.0p', // don't know what this is...
			];
			return self::LINK . '/@' . implode(',', $params);
		}
	}

	public function process(): void
	{
		if ($this->data->mapCoords instanceof Coordinates) {
			$coords = $this->data->mapCoords;
			$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_MAP);
			$this->collection->add($location);
		}

	}
}
