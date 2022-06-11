<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\Coordinates;
use Lvht\GeoHash;

/**
 * Class GeohashService
 * @link http://geohash.org
 * @link https://en.wikipedia.org/wiki/Geohash
 */
final class GeohashService extends AbstractService
{
	const ID = 19;
	const NAME = 'Geohash';

	const LINK = 'http://geohash.org';

	const DEFAULT_PRECISION = 0.000001;  // precision to 6 decimal places in WGS84 format
	const RE = '[0123456789bcdefghjkmnpqrstuvwxyz]';
	const RE_IN_STRING = '/(^|\s)(' . self::RE . '{8,})(\s|$)/i';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_TEXT,
		ServicesManager::TAG_GENERATE_TEXT_OFFLINE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$geohash = GeoHash::encode($lon, $lat, self::DEFAULT_PRECISION);
			return sprintf('%s/%s', self::LINK, $geohash);
		}
	}

	public function isValid(): bool
	{
		return $this->isUrl() || $this->isCode();
	}

	public function process(): void
	{
		assert($this->data->coords instanceof Coordinates);
		$lat = $this->data->coords->getLat();
		$lon = $this->data->coords->getLon();
		$betterLocation = new BetterLocation($this->input, $lat, $lon, self::class);
		$betterLocation->setPrefixMessage(sprintf('<a href="%s/%s">%s</a> <code>%s</code>: ',
			self::LINK, $this->data->code, self::NAME, $this->data->code
		));
		$this->collection->add($betterLocation);
	}

	/** @example http://geohash.org/u2fkbnhu9cxe */
	public function isUrl(): bool
	{
		if ($this->url && $this->url->getDomain(0) === 'geohash.org') {
			if (preg_match('/^\/(' . self::RE . '{1,})/', $this->url->getPath(), $matches)) {
				$this->data->code = $matches[1];
				$this->data->coords = self::codeToCoords($this->data->code);
				return true;
			}
		}
		return false;
	}

	/** @example u2fkbnhu9cxe */
	public function isCode(): bool
	{
		if (preg_match('/^(' . self::RE . '{1,})$/', $this->input, $matches)) {
			$this->data->code = $matches[1];
			$this->data->coords = self::codeToCoords($this->data->code);
			return true;
		}
		return false;
	}

	public static function findInText(string $input): BetterLocationCollection
	{
		$collection = new BetterLocationCollection();
		return $collection;  // searching in string is currently disabled, because codes are too similar to normal words
		if (preg_match_all(self::RE_IN_STRING, $input, $matches)) {
			foreach ($matches[2] as $plusCode) {
				$collection->add(self::processStatic($plusCode)->getCollection());
			}
		}
		return $collection;
	}

	public static function getShareText(float $lat, float $lon): string
	{
		return GeoHash::encode($lon, $lat, self::DEFAULT_PRECISION);
	}

	/** Get restult from Geohash library and return point in the middle between two coordinates */
	public static function codeToCoords(string $code): Coordinates
	{
		list($lonMin, $lonMax, $latMin, $latMax) = GeoHash::decode($code);
		return new Coordinates(
			($latMin + $latMax) / 2,
			($lonMin + $lonMax) / 2,
		);
	}

}
