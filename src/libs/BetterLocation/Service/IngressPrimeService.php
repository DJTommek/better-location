<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\Ingress;
use App\Utils\Strict;
use Nette\Http\UrlImmutable;

/**
 * Parsing Ingress links generated in Ingress Prime application
 */
final class IngressPrimeService extends AbstractService
{
	const ID = 40;
	const NAME = 'Ingress Prime';

	const TYPE_PORTAL = 'portal';
	const TYPE_MISSION = 'mission';

	public function __construct(
		private readonly \App\IngressLanchedRu\Client $ingressClient,
		private readonly IngressIntelService $ingressIntelService,
	) {
	}

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public function validate(): bool
	{
		$result = false;
		if (
			$this->url
			&& $this->url->getDomain(0) === 'link.ingress.com'
			&& $this->url->getPath() === '/'
		) {
			if (Strict::isUrl($this->url->getQueryParameter('link'))) {
				$realPortalLink = new UrlImmutable($this->url->getQueryParameter('link'));
				if ($realPortalLink->getDomain(0) === 'intel.ingress.com') {
					if (preg_match('/^\/portal\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $realPortalLink->getPath(), $matches)) {
						$this->data->portalGuid = $matches[1];
						$result = true;
					} else if (preg_match('/^\/mission\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $realPortalLink->getPath(), $matches)) {
						$this->data->missionGuid = $matches[1];
						$result = true;
					}
				}
			}

			$oflLink = $this->url->getQueryParameter('ofl');
			if (Strict::isUrl($oflLink)) {
				$this->ingressIntelService->setInput($oflLink);
				if ($this->ingressIntelService->validate()) {
					$this->data->oflLink = $oflLink;
					$this->data->oflLinkService = $this->ingressIntelService;
					$result = true;
				}
			}
		}
		return $result;
	}

	public function process(): void
	{
		$mainCoords = null;

		if (
			isset($this->data->portalGuid)
			&& $portal = $this->ingressClient->getPortalByGUID($this->data->portalGuid)
		) {
			$location = new BetterLocation($this->input, $portal->lat, $portal->lng, self::class, self::TYPE_PORTAL);
			Ingress::rewritePrefixes($location, $portal);
			$location->addDescription('', Ingress::BETTER_LOCATION_KEY_PORTAL); // Prevent generating Ingress description
			$this->collection->add($location);
			$mainCoords = $location->getCoordinates();
		}

		if (isset($this->data->missionGuid)) {
			// @TODO load mission info and probably generate BetterLocation for mission start (first portal)
		}

		if (isset($this->data->oflLink)) {
			$this->ingressIntelService->process();
			foreach ($this->ingressIntelService->getCollection() as $oflLocation) {
				if ($mainCoords === null || $mainCoords->getLatLon() !== $oflLocation->getLatLon()) {
					// Add to main collection only if is different than main location (portal hash, mission, ...)
					$this->collection->add($oflLocation);
				}
			}
		}
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_PORTAL,
			self::TYPE_MISSION,
		];
	}
}
