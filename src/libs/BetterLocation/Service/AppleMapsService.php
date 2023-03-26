<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\ServicesManager;
use App\Utils\Coordinates;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;

final class AppleMapsService extends AbstractService
{
	const ID = 47;
	const NAME = 'AppleMaps';
	const NAME_SHORT = 'Apple';

	const LINK = 'https://maps.apple.com';

	const LINK_SHARE = 'https://maps.apple.com/?ll=%1$F,%2$F&q=%1$F,%2$F';
	const LINK_DRIVE = 'https://maps.apple.com/?daddr=%1$F,%2$F&dirflg=d';

	const TYPE_MAP_CENTER = 'Map center';
	const TYPE_PLACE = 'Place';
	const TYPE_DESTINATION = 'Destination';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		return sprintf($drive ? self::LINK_DRIVE : self::LINK_SHARE, $lat, $lon);
	}


	public function isValid(): bool
	{
		$result = false;

		if ($this->url === null || $this->url->getDomain(0) !== 'maps.apple.com') {
			return false;
		}

		if ($param = $this->url->getQueryParameter('ll')) { // map coordinates
			$coords = Coordinates::fromString($param);
			if ($coords !== null) {
				// Hybrid location. If 'q' is defined, then it is label for specific location. Otherwise it is just map center.
				// From docs:
				// The q parameter can also be used as a label if the location is explicitly defined in the ll or address parameters.
				// A URL-encoded string that describes the search object, such as “pizza,” or an address to be geocoded
				$title = $this->getTitle($this->url);
				if ($title === null) {
					$this->data->mapCoords = $coords;
				} else {
					$this->data->placeCoords = $coords;
					$this->data->placeTitle = $title;
				}
				$result = true;
			}
		}

		if ($param = $this->url->getQueryParameter('daddr')) { // navigation destination
			$coords = Coordinates::fromString($param);
			if ($coords !== null) {
				$this->data->addressTarget = $coords;
				$result = true;
			}
		}

		return $result;
	}

	public function process(): void
	{
		if ($this->data->mapCoords ?? false) {
			$location = new BetterLocation($this->input, $this->data->mapCoords->getLat(), $this->data->mapCoords->getLon(), self::class, self::TYPE_MAP_CENTER);
			$this->collection->add($location);
		}

		if ($this->data->placeCoords ?? false) {
			$location = new BetterLocation($this->input, $this->data->placeCoords->getLat(), $this->data->placeCoords->getLon(), self::class, self::TYPE_PLACE);
			$location->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $this->url, self::NAME, $this->data->placeTitle));
			$this->collection->add($location);
		}

		if ($this->data->addressTarget ?? false) {
			$location = new BetterLocation($this->input, $this->data->addressTarget->getLat(), $this->data->addressTarget->getLon(), self::class, self::TYPE_DESTINATION);
			$this->collection->add($location);
		}
	}

	private function getTitle(UrlImmutable|Url $url): ?string
	{
		$title = $url->getQueryParameter('q');
		return $title === null ? null : trim(urldecode($title));
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP_CENTER,
			self::TYPE_PLACE,
			self::TYPE_DESTINATION,
		];
	}
}
