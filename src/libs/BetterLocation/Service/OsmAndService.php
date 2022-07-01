<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ServicesManager;
use App\Utils\Coordinates;
use App\Utils\Strict;
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

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		return self::LINK . sprintf('/go.html?lat=%1$f&lon=%2$f', $lat, $lon);
	}

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'osmand.net' &&
			Arrays::contains(['/go', '/go.html'], $this->url->getPath()) &&
			Coordinates::isLat($this->url->getQueryParameter('lat')) &&
			Coordinates::isLat($this->url->getQueryParameter('lon'))
		);
	}

	public function process(): void
	{
		$location = new BetterLocation(
			$this->inputUrl,
			Strict::floatval($this->url->getQueryParameter('lat')),
			Strict::floatval($this->url->getQueryParameter('lon')),
			self::class
		);
		$this->collection->add($location);
	}
}
