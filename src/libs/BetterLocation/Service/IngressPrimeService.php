<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Icons;
use App\Ingress\MissionInterface;
use App\Ingress\MissionProvider;
use App\Ingress\PortalProvider;
use App\Ingress\WaypointKind;
use App\Utils\Ingress;
use App\Utils\Strict;
use Nette\Http\Url;
use Nette\Http\UrlImmutable;
use Tracy\Debugger;
use Tracy\ILogger;

/**
 * Parsing Ingress links generated in Ingress Prime application
 */
final class IngressPrimeService extends AbstractService
{
	const int ID = 40;
	const string NAME = 'Ingress Prime';

	const string TYPE_PORTAL = 'portal';
	const string TYPE_MISSION = 'mission';

	private ?string $portalGuid = null;
	private ?string $missionGuid = null;
	private ?string $oflLink = null;

	public function __construct(
		private readonly PortalProvider $ingressClient,
		private readonly IngressIntelService $ingressIntelService,
		private readonly ?MissionProvider $missionProvider = null,
	) {
	}

	/** @throws NotSupportedException */
	#[\Override]
 public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	#[\Override]
 public function validate(): bool
	{
		if (
			!$this->url
			|| $this->isDomainValid($this->url) === false
		) {
			return false;
		}

		// Example: https://link.ingress.com/portal/cf2e28687bfe34fca1c2fdbb966a484f.16
		$baseLinkValid = $this->subvalidateLink($this->url);

		if ($baseLinkValid === false) {
			// link might be older format created before ~2025-08-20, see https://t.me/IUENG_Extra/470
			// Example: https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F0bd94fac5de84105b6eef6e7e1639ad9.12
			$paramLink = $this->url->getQueryParameter('link');
			if (Strict::isUrl($paramLink)) {
				$this->subvalidateLink($paramLink);
			}
		}

		$oflLink = $this->url->getQueryParameter('ofl');
		if ($this->isDomainValid($oflLink)) {
			$this->ingressIntelService->setInput($oflLink);
			if ($this->ingressIntelService->validate()) {
				$this->oflLink = $oflLink;
			}
		}

		return $this->portalGuid !== null || $this->missionGuid !== null || $this->oflLink !== null;
	}

	private function isDomainValid(UrlImmutable|Url|string|null $url): bool
	{
		if ($url === null) {
			return false;
		}

		if (is_string($url)) {
			if (Strict::isUrl($url) === false) {
				return false;
			}
			$url = new UrlImmutable($url);
		}

		return in_array($url->getDomain(0), ['link.ingress.com', 'intel.ingress.com'], true);
	}

	private function subvalidateLink(UrlImmutable|Url|string $url): bool
	{
		if (is_string($url)) {
			$url = new UrlImmutable($url);
		}

		if ($this->isDomainValid($url) === false) {
			return false;
		}

		if (preg_match('/^\/portal\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $url->getPath(), $matches)) {
			assert(isset($this->portalGuid) === false, 'Portal GUID already set, check code for conflict. If valid usecase, both GUIDs should be processed for location');
			$this->portalGuid = $matches[1];
			return true;
		}

		if (preg_match('/^\/mission\/([0-9a-z]{32}\.[0-9a-f]{1,2})$/', $url->getPath(), $matches)) {
			assert(isset($this->missionGuid) === false, 'Mission GUID already set, check code for conflict. If valid usecase, both GUIDs should be processed for location');
			$this->missionGuid = $matches[1];
			return true;
		}

		return false;
	}

	#[\Override]
 public function process(): void
	{
		$mainCoords = null;

		if (
			isset($this->portalGuid)
			&& $portal = $this->ingressClient->getPortalByGUID($this->portalGuid)
		) {
			$location = new BetterLocation($this->input, $portal->getLat(), $portal->getLon(), self::class, self::TYPE_PORTAL);
			Ingress::rewritePrefixes($location, $portal);
			$location->addDescription('', Ingress::BETTER_LOCATION_KEY_PORTAL); // Prevent generating Ingress description
			$this->collection->add($location);
			$mainCoords = $location->getCoordinates();
		}

		if (isset($this->missionGuid) && $this->missionProvider !== null) {
			try {
				$location = $this->processMission($this->missionGuid);
				if ($location !== null) {
					$this->collection->add($location);
					$mainCoords ??= $location->getCoordinates();
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
			}
		}

		if (isset($this->oflLink)) {
			$this->ingressIntelService->process();
			foreach ($this->ingressIntelService->getCollection() as $oflLocation) {
				if ($mainCoords === null || $mainCoords->getLatLon() !== $oflLocation->getLatLon()) {
					// Add to main collection only if is different than main location (portal hash, mission, ...)
					$this->collection->add($oflLocation);
				}
			}
		}
	}

	/**
	 * Generate location of mission start (first waypoint with known location).
	 * @TODO is possible to add mission length?
	 * @TODO is possible to add average mission time?
	 */
	private function processMission(string $missionGuid): ?BetterLocation
	{
		assert($this->missionProvider !== null);
		$mission = $this->missionProvider->getMissionByGUID($missionGuid);
		$startWaypoint = $mission?->getStartWaypoint();
		if ($startWaypoint === null) {
			return null;
		}
		$startCoords = $startWaypoint->getCoordinates();
		assert($startCoords !== null);

		$location = new BetterLocation($this->input, $startCoords->getLat(), $startCoords->getLon(), self::class, self::TYPE_MISSION);

		$missionTitle = htmlspecialchars($mission->getTitle() ?? 'Unknown mission');
		$location->setInlinePrefixMessage(sprintf('Mission %s', $missionTitle));
		$prefix = sprintf(
			'Mission <a href="%s">%s %s</a> <a href="%s">%s</a>',
			$mission->getPrimeLink(),
			$missionTitle,
			Icons::INGRESS_PRIME,
			$mission->getIntelLink(),
			Icons::INGRESS_INTEL,
		);
		$imageLink = $mission->getImageLink(10_000);
		if ($imageLink !== null) {
			$prefix .= sprintf(' <a href="%s">%s</a>', $imageLink, Icons::INGRESS_PORTAL_IMAGE);
		}
		$location->setPrefixMessage($prefix);

		$location->addDescription(self::missionSummary($mission));

		$startPortalGuid = $startWaypoint->getPortalGuid();
		if ($startWaypoint->getKind() === WaypointKind::Portal && $startPortalGuid !== null) {
			$location->addDescription(
				sprintf(
					'Start portal: <a href="%s">%s %s</a> <a href="%s">%s</a>',
					Ingress::generatePrimePortalLink($startPortalGuid),
					htmlspecialchars($startWaypoint->getTitle() ?? 'Unknown portal'),
					Icons::INGRESS_PRIME,
					Ingress::generateIntelPortalLink($startCoords->getLat(), $startCoords->getLon()),
					Icons::INGRESS_INTEL,
				),
				Ingress::BETTER_LOCATION_KEY_PORTAL,
			);
		} else {
			$location->addDescription('', Ingress::BETTER_LOCATION_KEY_PORTAL); // Prevent generating Ingress description
		}

		return $location;
	}

	/**
	 * Eg. "6 waypoints, sequential, by DJTommek (Enlightened)"
	 */
	private static function missionSummary(MissionInterface $mission): string
	{
		$waypointsCount = count($mission->getWaypoints());
		$parts = [
			sprintf('%d %s', $waypointsCount, $waypointsCount === 1 ? 'waypoint' : 'waypoints'),
		];
		$kind = $mission->getKind();
		if ($kind !== null) {
			$parts[] = $kind->label();
		}
		$authorName = $mission->getAuthorName();
		if ($authorName !== null) {
			$author = 'by ' . htmlspecialchars($authorName);
			$authorFaction = $mission->getAuthorFaction();
			if ($authorFaction !== null) {
				$author .= sprintf(' (%s)', $authorFaction->label());
			}
			$parts[] = $author;
		}
		return implode(', ', $parts);
	}

	#[\Override]
 public static function getConstants(): array
	{
		return [
			self::TYPE_PORTAL,
			self::TYPE_MISSION,
		];
	}
}
