<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use DJTommek\Coordinates\CoordinatesImmutable;

final class IKatastrCzService extends AbstractService
{
	const int ID = 63;
	const string NAME = 'iKatastr.cz';

	const string LINK = 'https://www.ikatastr.cz/';

	public const array TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public const string TYPE_MAP = 'map';
	public const string TYPE_INFO = 'info';

	private const string QUERY_KEY_MAP = 'kde';
	private const string QUERY_KEY_INFO = 'info';

	private ?CoordinatesImmutable $coordsMap = null;
	private ?CoordinatesImmutable $coordsInfo = null;

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP,
			self::TYPE_INFO,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		}

		$latLon = sprintf('%F,%F', $lat, $lon);
		$zoom = 17; // 2026-04-12 - this is close enough to show parcel borders overlay in map

		$query = self::QUERY_KEY_MAP . '=' . $latLon . ',' . $zoom . '&' . self::QUERY_KEY_INFO . '=' . $latLon;
		return self::LINK . '#' . $query;
	}

	public function validate(): bool
	{
		if ($this->url?->getDomain(2) !== 'ikatastr.cz') {
			return false;
		}

		parse_str($this->url->fragment, $query);

		$coordsRawMap = explode(',', $query[self::QUERY_KEY_MAP] ?? '');
		$coordsRawInfo = explode(',', $query[self::QUERY_KEY_INFO] ?? '');

		if (count($coordsRawMap) >= 2) {
			$this->coordsMap = CoordinatesImmutable::safe($coordsRawMap[0], $coordsRawMap[1]);
		}
		if (count($coordsRawInfo) >= 2) {
			$this->coordsInfo = CoordinatesImmutable::safe($coordsRawInfo[0], $coordsRawInfo[1]);
		}

		return $this->coordsMap !== null || $this->coordsInfo !== null;
	}

	public function process(): void
	{
		assert($this->coordsMap !== null || $this->coordsInfo !== null);

		if ($this->coordsMap !== null) {
			$betterLocation = new BetterLocation($this->input, $this->coordsMap->lat, $this->coordsMap->lon, self::class, self::TYPE_MAP);
			$this->collection->add($betterLocation);
		}
		if ($this->coordsInfo !== null) {
			$betterLocation = new BetterLocation($this->input, $this->coordsInfo->lat, $this->coordsInfo->lon, self::class, self::TYPE_INFO);
			$this->collection->add($betterLocation);
		}
	}
}
