<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\Ingress;
use DJTommek\Coordinates\Coordinates;
use DJTommek\Coordinates\CoordinatesImmutable;
use DJTommek\Coordinates\CoordinatesInterface;

final class NianticLightshipService extends AbstractService
{
	const ID = 62;
	const NAME = 'Lightship';

	const TYPE_MAP_CENTER = 'map center';
	const TYPE_VENUE = 'venue';

	private CoordinatesInterface|null $mapCenterCoords = null;
	private string|null $venueGuid = null;

	public function __construct(
		private readonly \App\IngressLanchedRu\Client $ingressClient,
	) {
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			// @TODO coordinates could be used to search for portal in Ingress API
			return (string)Ingress::generateNianticLightshipLink(new Coordinates($lat, $lon));
		}
	}

	public function validate(): bool
	{

		// https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16
		//                                                  \______________/ \_______________/ \___/ \______________________________/ \_________________________________/
		//                                                   \ map center lat, lon and zoom level  /  \       opened mesh ID       /   \ object ID (a.k.a. Ingress portal GUID)
		if (
			$this->url === null
			|| $this->url->getDomain(0) !== 'lightship.dev'
		) {
			return false;
		}

		$path = mb_strtolower($this->url->getPath());
		if (str_starts_with($path, '/account/geospatial-browser/') === false) {
			return false;
		}

		$pathSegments = explode('/', $path);
		$params = explode(',', $pathSegments[3]);

		$this->mapCenterCoords = CoordinatesImmutable::safe($params[0] ?? null, $params[1] ?? null);
		$venueGuid = $params[4] ?? null;
		if ($venueGuid !== null && Ingress::isGuid($venueGuid)) {
			$this->venueGuid = $venueGuid;
		}

		return $this->mapCenterCoords !== null || $this->venueGuid !== null;
	}

	public function process(): void
	{
		if ($this->venueGuid !== null) {
			$portal = $this->ingressClient->getPortalByGUID($this->venueGuid);
			$location = new BetterLocation(
				$this->input,
				$portal->lat,
				$portal->lng,
				self::class,
				self::TYPE_VENUE,
			);
			Ingress::appendPortalDataDescription($location, $portal);
			$this->collection->add($location);
		}

		if ($this->mapCenterCoords !== null) {
			$location = new BetterLocation(
				$this->input,
				$this->mapCenterCoords->getLat(),
				$this->mapCenterCoords->getLon(),
				self::class,
				self::TYPE_MAP_CENTER,
			);
			$this->collection->add($location);
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_MAP_CENTER,
			self::TYPE_VENUE,
		];
	}
}
