<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Icons;
use App\Utils\Ingress;
use App\Utils\Requestor;
use App\Utils\Strict;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesInterface;
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
		$dom = Utils::domFromUTF8($body);
		$eventName = $dom->getElementsByTagName('h2')->item(0)->textContent;

		$basePortalLocation = null;
		$basePortalName = null;

		$restockPortalLocation = null;
		$restockPortalName = null;

		foreach ($dom->getElementById('listing')->childNodes as $rawEl) {
			if (!isset($rawEl->data)) {
				continue;
			}

			$searchedText = trim((string)$rawEl->data);
			switch ($searchedText) {
				case 'Base Portal:':
					[$basePortalLocation, $basePortalName] = $this->extractPortalData($rawEl);
					if ($basePortalLocation === null) {
						continue 2;
					}
					assert($basePortalLocation instanceof CoordinatesInterface);
					break;
				case 'Restocking Portal:':
					[$restockPortalLocation, $restockPortalName] = $this->extractPortalData($rawEl);
					if ($restockPortalLocation === null) {
						continue 2;
					}
					assert($restockPortalLocation instanceof CoordinatesInterface);
					break;
			}
		}

		$portalLink = $basePortalLocation ?? $restockPortalLocation;
		if ($portalLink === null) {
			return;
		}

		$location = new BetterLocation(
			$this->inputUrl,
			$portalLink->getLat(),
			$portalLink->getLon(),
			self::class,
		);
		$location->setPrefixMessage(sprintf('<a href="%s">%s</a>', $this->inputUrl, htmlspecialchars($eventName)));
		$this->injectPortalDataIntoEventLocation($location, $basePortalLocation, 'Base portal', $basePortalName ?? 'Unknown name');
		$this->injectPortalDataIntoEventLocation($location, $restockPortalLocation, 'Restock portal', $restockPortalName ?? 'Unknown name');
		$this->collection->add($location);
	}

	/**
	 * @return array{BetterLocation, string}|null
	 */
	private function extractPortalData(\DOMNode $element): ?array
	{
		assert($element instanceof \DOMText);

		$portalNameEl = $element->nextElementSibling;
		assert($portalNameEl->tagName === 'span');
		$portalName = $portalNameEl->nodeValue;

		$portalIntelLinkEl = $element->nextElementSibling->nextElementSibling;
		assert($portalIntelLinkEl->tagName === 'a');
		assert($portalIntelLinkEl->nodeValue === 'Intel Link');
		$portalIntelLink = $portalIntelLinkEl->getAttribute('href');

		$this->ingressIntelService->setInput($portalIntelLink);
		if ($this->ingressIntelService->validate() === false) {
			return null;
		}
		$this->ingressIntelService->process();
		$location = $this->ingressIntelService->getFirst();

		return [$location, $portalName];
	}

	private function injectPortalDataIntoEventLocation(
		BetterLocation $eventLocation,
		?CoordinatesInterface $portalLocation,
		string $descriptionPrefix,
		string $portalName,
	): void {
		if ($portalLocation === null) {
			return;
		}

		try {
			$portal = $this->ingressClient->getPortalByCoords($portalLocation->getLat(), $portalLocation->getLon());
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
			return;
		}

		// Unable to load portal data in API, fallback to simplified Intel link
		if ($portal === null) {
			$description = sprintf('<a href="%s">%s %s</a>',
				Ingress::generateIntelPortalLink($portalLocation->getLat(), $portalLocation->getLon()),
				htmlspecialchars($portalName),
				Icons::INGRESS_INTEL,
			);
		} else {
			$description = Ingress::generatePortalLinkMessage($portal);
		}

		$eventLocation->addDescription(
			sprintf('%s: %s', $descriptionPrefix, $description),
			(!$eventLocation->hasDescription(Ingress::BETTER_LOCATION_KEY_PORTAL) ? Ingress::BETTER_LOCATION_KEY_PORTAL : null),
		);

		if ($portal !== null && in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
			$eventLocation->setAddress(htmlspecialchars($portal->address));
		}
	}
}
