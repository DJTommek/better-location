<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Utils\Ingress;
use DJTommek\Coordinates\Coordinates;

final class IngressIntelService extends AbstractService
{
	const ID = 9;
	const NAME = 'Ingress';

	const TYPE_MAP = 'map';
	const TYPE_PORTAL = 'portal';

	const LINK = 'https://intel.ingress.com';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public function __construct(
		private readonly \App\IngressLanchedRu\Client $ingressClient,
	) {
	}

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/?ll=%1$F,%2$F&pll=%1$F,%2$F', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		if ($this->url && $this->url->getDomain(2) === 'ingress.com') {

			$this->data->portalCoords = Coordinates::fromString(
				$this->inputUrl->getQueryParameter('pll') ?? '',
			);

			$this->data->mapCoords = Coordinates::fromString(
				$this->inputUrl->getQueryParameter('ll') ?? '',
			);
		}

		return $this->data->portalCoords !== null || $this->data->mapCoords !== null;
	}

	public function process(): void
	{
		if ($this->data->portalCoords !== null) {
			$location = new BetterLocation($this->input, $this->data->portalCoords->lat, $this->data->portalCoords->lon, self::class, self::TYPE_PORTAL);

			if ($portal = $this->ingressClient->getPortalByCoords($location->getLat(), $location->getLon())) {
				Ingress::rewritePrefixes($location, $portal);
				$location->addDescription('', Ingress::BETTER_LOCATION_KEY_PORTAL); // Prevent generating Ingress description
			}
			$this->collection->add($location);
		}

		if ($this->data->mapCoords !== null) {
			$location = new BetterLocation($this->input, $this->data->mapCoords->lat, $this->data->mapCoords->lon, self::class, self::TYPE_MAP);

			if ($portal = $this->ingressClient->getPortalByCoords($location->getLat(), $location->getLon())) {
				Ingress::rewritePrefixes($location, $portal);
				$location->addDescription('', Ingress::BETTER_LOCATION_KEY_PORTAL); // Prevent generating Ingress description
			}
			$this->collection->add($location);
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_PORTAL,
			self::TYPE_MAP,
		];
	}
}
