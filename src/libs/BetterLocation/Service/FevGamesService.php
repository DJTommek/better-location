<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Ingress;
use App\Utils\Requestor;
use App\Utils\Strict;
use Tracy\Debugger;

final class FevGamesService extends AbstractService
{
	const ID = 24;
	const NAME = 'FevGames';

	const LINK = 'https://fevgames.net';

	public function __construct(
		private readonly \App\IngressLanchedRu\Client $ingressClient,
		private readonly IngressIntelService $ingressIntelService,
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'fevgames.net' &&
			$this->url->getPath() === '/ifs/event/' &&
			Strict::isPositiveInt($this->url->getQueryParameter('e'))
		);
	}

	public function process(): void
	{
		$body = $this->requestor->get($this->url, Config::CACHE_TTL_FEVGAMES);
		$dom = new \DOMDocument();
		@$dom->loadHTML($body);
		foreach ($dom->getElementsByTagName('a') as $linkEl) {
			$link = $linkEl->getAttribute('href');
			$intelService = $this->ingressIntelService->setInput($link);
			if ($intelService->validate()) {
				$data = $intelService->getData();
				if ($data->portalCoord) {
					$location = new BetterLocation($this->inputUrl, $data->portalCoordLat, $data->portalCoordLon, self::class);
					$eventName = $dom->getElementsByTagName('h2')->item(0)->textContent;
					$location->setPrefixMessage(sprintf('<a href="%s">%s</a>', $this->inputUrl, htmlentities($eventName)));
					$this->addPortalData($location);
					$this->collection->add($location);
				}
			}
		}
	}

	private function addPortalData(BetterLocation $location): void
	{
		try {
			$portal = $this->ingressClient->getPortalByCoords($location->getLat(), $location->getLon());
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
			return;
		}
		if ($portal) {
			$location->addDescription(
				'Registration portal: ' . Ingress::generatePortalLinkMessage($portal),
				Ingress::BETTER_LOCATION_KEY_PORTAL,
			);

			if (in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
				$location->setAddress(htmlspecialchars($portal->address));
			}
		}
	}
}
