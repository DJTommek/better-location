<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\ServicesManager;
use App\Utils\Requestor;
use App\Utils\Strict;
use DJTommek\Coordinates\Coordinates;

final class OpenStreetMapService extends AbstractService
{
	const ID = 7;
	const NAME = 'OpenStreetMap';
	const NAME_SHORT = 'OSM';

	const LINK = 'https://www.openstreetmap.org';
	const API_LINK = 'https://api.openstreetmap.org/api/';

	const TYPE_MAP = 'Map';
	const TYPE_POINT = 'Point';
	const TYPE_NOTE = 'Note';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
		ServicesManager::TAG_GENERATE_LINK_DRIVE,
	];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			return self::LINK . sprintf('/directions?from=&to=%1$F,%2$F', $lat, $lon);
		} else {
			return self::LINK . sprintf('/search?whereami=1&query=%1$F,%2$F&mlat=%1$F&mlon=%2$F#map=17/%1$F/%2$F', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		if (
			!$this->url
			|| !in_array($this->url->getDomain(2), ['openstreetmap.org', 'osm.org'], true)
		) {
			return false;
		}

		if (str_starts_with($this->url->getPath(), '/go/')) {
			$this->data->isShortUrl = true;
			return true;
		}

		$this->data->pointCoord = Coordinates::safe(
			$this->url->getQueryParameter('mlat'),
			$this->url->getQueryParameter('mlon'),
		);

		$this->data->mapCoord = null;
		if ($this->url->getFragment()) {
			parse_str($this->url->getFragment(), $fragments);
			if (isset($fragments['map'])) {
				$coords = explode('/', $fragments['map']);
				if (count($coords) >= 3) {
					$this->data->mapCoord = Coordinates::safe($coords[1], $coords[2]);
				}
			}
		}

		if (preg_match('/^\/note\/([0-9]+)\/?$/', $this->url->getPath(), $matches)) {
			$this->data->noteId = $matches[1];
		}

		return $this->data->pointCoord !== null || $this->data->mapCoord !== null || $this->data->noteId !== null;
	}

	public function process(): void
	{
		if ($this->data->isShortUrl ?? false) {
			$this->url->setHost('www.openstreetmap.org');
			$this->url = Strict::url($this->requestor->loadFinalRedirectUrl($this->url));
			if ($this->validate() === false) {
				throw new InvalidLocationException(sprintf('Unexpected redirect URL "%s" from short URL "%s".', $this->url, $this->inputUrl));
			}
		}

		if ($this->data->noteId !== null) {
			$note = $this->requestor->getJson(sprintf('%s/0.6/notes/%d.json', self::API_LINK, $this->data->noteId));
			if ($note !== null) {
				assert($note->geometry->type === 'Point');
				$this->collection->add(new BetterLocation($this->inputUrl, $note->geometry->coordinates[1], $note->geometry->coordinates[0], self::class, self::TYPE_NOTE));
			}
		}

		if ($this->data->pointCoord !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->pointCoord->lat, $this->data->pointCoord->lon, self::class, self::TYPE_POINT));
		}

		if ($this->data->mapCoord !== null) {
			$this->collection->add(new BetterLocation($this->inputUrl, $this->data->mapCoord->lat, $this->data->mapCoord->lon, self::class, self::TYPE_MAP));
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_POINT,
			self::TYPE_MAP,
			self::TYPE_NOTE,
		];
	}
}
