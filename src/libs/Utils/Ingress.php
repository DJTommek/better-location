<?php declare(strict_types=1);

namespace App\Utils;

use App\BetterLocation\BetterLocation;
use App\Factory;
use App\Icons;
use App\IngressLanchedRu\Types\PortalType;
use Nette\Http\Url;
use Tracy\Debugger;

class Ingress
{
	public const BETTER_LOCATION_KEY_PORTAL = 'ingressPortal';

	public static function generatePortalLinkMessage(PortalType $portal): string
	{
		return sprintf('<a href="%s">%s %s</a> <a href="%s">%s</a> <a href="%s">%s</a>',
			$portal->getPrimeLink(),
			htmlspecialchars($portal->name),
			Icons::INGRESS_PRIME,
			$portal->getIntelLink(),
			Icons::INGRESS_INTEL,
			$portal->getImageLink(10_000),
			Icons::INGRESS_PORTAL,
		);
	}

	/**
	 * Rewrite BetterLocation prefixes. Use only for Ingress-related services
	 */
	public static function rewritePrefixes(BetterLocation $location, PortalType $portal): void
	{
		$portalName = htmlspecialchars($portal->name);
		$location->setInlinePrefixMessage($portalName);
		$location->setPrefixMessage(self::generatePortalLinkMessage($portal));
	}

	/**
	 * Check if there is portal on exact coordinates of location and eventually ppend portal links as description.
	 */
	public static function setPortalDataDescription(BetterLocation $location): void
	{
		try {
			if ($portal = Factory::ingressLanchedRu()->getPortalByCoords($location->getLat(), $location->getLon())) {
				$location->addDescription(
					'Ingress portal: ' . self::generatePortalLinkMessage($portal),
					self::BETTER_LOCATION_KEY_PORTAL
				);

				if (in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
					$location->setAddress(htmlspecialchars($portal->address));
				}
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}

	public static function generateIntelMissionLink(string $guid): string
	{
		return 'https://intel.ingress.com/mission/' . $guid;
	}

	public static function generateIntelPortalLink(float $lat, float $lon): string
	{
		return sprintf('https://intel.ingress.com/intel?pll=%F,%F', $lat, $lon);
	}

	/**
	 * Generate special link, which can be used to open specific portal in Ingress Prime application
	 */
	public static function generatePrimePortalLink(string $guid, float $lat, float $lon): Url
	{
		$url = new Url('https://link.ingress.com/');
		$url->setQueryParameter('link', 'https://intel.ingress.com/portal/' . $guid);
		$url->setQueryParameter('apn', 'com.nianticproject.ingress');
		$url->setQueryParameter('isi', 576505181);
		$url->setQueryParameter('ibi', 'com.google.ingress');
		$url->setQueryParameter('ifl', 'https://apps.apple.com/app/ingress/id576505181');
		$url->setQueryParameter('ofl', self::generateIntelPortalLink($lat, $lon));
		return $url;
	}

	/**
	 * Generate special link, which can be used to open specific mission in Ingress Prime application
	 */
	public static function generatePrimeMissionLink(string $guid): Url
	{
		$missionIntelLink = self::generateIntelMissionLink($guid);
		$url = new Url('https://link.ingress.com/');
		$url->setQueryParameter('link', $missionIntelLink);
		$url->setQueryParameter('apn', 'com.nianticproject.ingress');
		$url->setQueryParameter('isi', 576505181);
		$url->setQueryParameter('ibi', 'com.google.ingress');
		$url->setQueryParameter('ifl', 'https://apps.apple.com/app/ingress/id576505181');
		$url->setQueryParameter('ofl', $missionIntelLink);
		return $url;
	}
}
