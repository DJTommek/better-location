<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\MiniCurl\Exceptions\InvalidResponseException;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\CoordinatesInterface;
use Nette\Http\Url;

final class BaladIrService extends AbstractService
{
	public const ID = 46;
	public const NAME = 'Balad.ir';
	public const LINK = 'https://balad.ir/';

	public const TYPE_MAP_CENTER = 'Map center';
	public const TYPE_PLACE_COORDS = 'Place coords';
	public const TYPE_PLACE = 'Place';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP_CENTER,
			self::TYPE_PLACE_COORDS,
			self::TYPE_PLACE,
		];
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'balad.ir' &&
			$this->isValidUrl()
		);
	}

	private function isValidUrl(): bool
	{
		$valid = false;

		// Map center
		// https://balad.ir/#15/35.8259/50.96629
		$regex = '/^(?<zoom>[0-9.]+)\/(?<lat>' . Coordinates::RE_BASIC_LAT . ')\/(?<lon>' . Coordinates::RE_BASIC_LON . ')$/';
		if (preg_match($regex, $this->url->getFragment(), $matches)) {
			$coords = Coordinates::safe($matches['lat'], $matches['lon']);
			if ($coords instanceof CoordinatesInterface) {
				$this->data->mapCenterCoords = $coords;
				$valid = true;
			}
		}

		// Map place coordinates
		// https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000
		if ($this->url->getPath() === '/location') {
			$coords = Coordinates::safe(
				$this->url->getQueryParameter('latitude'),
				$this->url->getQueryParameter('longitude'),
			);
			if ($coords instanceof CoordinatesInterface) {
				$this->data->placeCoords = $coords;
				$valid = true;
			}
		}

		// Map place with ID
		if ($this->isPlaceIdUrl()) {
			$valid = true;
		}

		return $valid;
	}

	private function isPlaceIdUrl(): bool
	{
		// https://balad.ir/p/3j08MFNHbCGvnu
		if (preg_match('/^\/p\/([a-zA-Z0-9]{3,})$/', $this->url->getPath(), $matches)) {
			$this->data->placeId = $matches[1];
			return true;
		}

		// https://balad.ir/p/%DA%A9%D9%88%DB%8C-%D8%A2%DB%8C%D8%AA-%D8%A7%D9%84%D9%84%D9%87-%D8%BA%D9%81%D8%A7%D8%B1%DB%8C-bandar-abbas_residential-complex-3j08MFNHbCGvnu?preview=true
		if (preg_match('/^\/p\/.+-([a-zA-Z0-9]{3,})$/', $this->url->getPath(), $matches)) {
			$this->data->placeId = $matches[1];
			return true;
		}

		return false;
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			// https://balad.ir/location?latitude=35.826644&longitude=50.968268&zoom=16.500000
			$url = new Url(self::LINK);
			$url->setPath('/location');
			$url->setQueryParameter('latitude', $lat);
			$url->setQueryParameter('longitude', $lon);
			return (string)$url;
		}
	}

	public function process(): void
	{
		if (isset($this->data->placeCoords)) {
			$coords = $this->data->placeCoords;
			$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_PLACE_COORDS);
			$this->collection->add($location);
		}

		if (isset($this->data->placeId)) {
			$place = $this->loadPlaceId($this->data->placeId);
			$coords = Coordinates::safe($place->geometry->coordinates[1] ?? null, $place->geometry->coordinates[0] ?? null);
			if ($coords !== null) {
				$location = new BetterLocation(
					$this->input,
					$coords->getLat(),
					$coords->getLon(),
					self::class,
					self::TYPE_PLACE
				);
				if (isset($place->name)) {
					$location->setPrefixMessage(sprintf(
						'<a href="%s">%s %s</a>',
						$this->input,
						self::NAME,
						htmlentities($place->name),
					));
				}

				$this->collection->add($location);
			}
		}

		// process map center only if there are no other locations
		if (isset($this->data->mapCenterCoords) && $this->collection->isEmpty()) {
			$coords = $this->data->mapCenterCoords;
			$location = new BetterLocation($this->input, $coords->getLat(), $coords->getLon(), self::class, self::TYPE_MAP_CENTER);
			$this->collection->add($location);
		}
	}

	private function loadPlaceId(string $placeId): ?\stdClass
	{
		try {
			$url = sprintf('https://poi.raah.ir/web/v4/%s?format=json', $placeId);
			$response = (new MiniCurl($url))
				->allowAutoConvertEncoding(false)
				->allowCache(Config::CACHE_TTL_RAAH_IR)
				->run();
			return $response->getBodyAsJson();
		} catch (InvalidResponseException $exception) {
			if ($exception->getCode() === 404) {
				return null;
			}
			throw $exception;
		}
	}
}
