<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\Ge0Code;

/**
 * @link https://organicmaps.app/
 * @link https://github.com/organicmaps/organicmaps
 */
final class OrganicMapsService extends AbstractService
{
	const ID = 18;
	const NAME = 'Organic Maps';

	const LINK = 'https://organicmaps.app';
	const LINK_SHARE = 'https://omaps.app';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			$ge0code = Ge0Code::encode($lat, $lon);
			return sprintf('%s/%s', self::LINK_SHARE, $ge0code->code);
		}
	}

	public function isValid(): bool
	{
		return $this->isUrl();
	}

	public function process(): void
	{
		assert($this->data->ge0 instanceof Ge0Code);
		$lat = $this->data->ge0->lat;
		$lon = $this->data->ge0->lon;
		$betterLocation = new BetterLocation($this->input, $lat, $lon, self::class);
		$this->collection->add($betterLocation);
	}

	/** @example https://omaps.app/s4G4aoSWF9 */
	public function isUrl(): bool
	{
		if ($this->url && $this->url->getDomain(0) === 'omaps.app') {
			list(, $ge0code) = explode('/', $this->url->getPath());
			if (Ge0Code::isValid($ge0code)) {
				$this->data->ge0 = Ge0Code::decode($ge0code);
				return true;
			}
		}
		return false;
	}
}
