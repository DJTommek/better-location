<?php declare(strict_types=1);

namespace App\Utils;

use App\BetterLocation\BetterLocation;
use App\Icons;
use App\IngressLanchedRu\Types\PortalType;
use Nette\Http\Url;

class Ingress
{
	public const BETTER_LOCATION_KEY_PORTAL = 'ingressPortal';

	public static function isGuid(string $input): bool
	{
		return preg_match('/^([0-9a-z]{32}\.[0-9a-f]{1,2})$/i', $input) === 1;
	}

	public static function generatePortalLinkMessage(PortalType $portal): string
	{
		return sprintf('<a href="%s">%s %s</a> <a href="%s">%s</a> <a href="%s">%s</a> <a href="%s">%s</a>',
			$portal->getPrimeLink(),
			htmlspecialchars($portal->name),
			Icons::INGRESS_PRIME,
			$portal->getIntelLink(),
			Icons::INGRESS_INTEL,
			$portal->getImageLink(10_000),
			Icons::INGRESS_PORTAL_IMAGE,
			$portal->getLightshipLink(),
			Icons::INGRESS_SCAN,
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

	public static function appendPortalDataDescription(BetterLocation $location, PortalType $portal): void
	{
		$location->addDescription(
			'Ingress portal: ' . self::generatePortalLinkMessage($portal),
			self::BETTER_LOCATION_KEY_PORTAL,
		);

		if (
			$location->hasAddress() === false
			&& in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false // show portal address only if it makes sense
		) {
			$location->setAddress(htmlspecialchars($portal->address));
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
		// https://link.ingress.com/?link=https%3A%2F%2Fintel.ingress.com%2Fportal%2F3e9f17ce6a824e67a1179961f175fe62.16&apn=com.nianticproject.ingress&isi=576505181&ibi=com.google.ingress&ifl=https%3A%2F%2Fapps.apple.com%2Fapp%2Fingress%2Fid576505181&ofl=https%3A%2F%2Fintel.ingress.com%2Fintel%3Fpll%3D50.11747%2C14.405216

		$url = new Url('https://link.ingress.com/');
		$url->setQueryParameter('link', 'https://intel.ingress.com/portal/' . $guid);
		$url->setQueryParameter('apn', 'com.nianticproject.ingress');
		$url->setQueryParameter('isi', 576505181);
		$url->setQueryParameter('ibi', 'com.google.ingress');
		$url->setQueryParameter('ifl', 'https://apps.apple.com/app/ingress/id576505181');
		$url->setQueryParameter('ofl', self::generateIntelPortalLink($lat, $lon));
		return $url;
	}

	public static function generateNianticLightshipLink(
		\DJTommek\Coordinates\CoordinatesInterface $coordinates,
		float|null $zoom = null,
		string|null $meshId = null,
		string|null $guid = null,
	): Url {
		// https://lightship.dev/account/geospatial-browser/50.0830485642698,14.42820958675955,15.69,13102D0F2EDC41BAB400A4D3FD672CEF,6a01961a5fc54df8b7efe45fc1f983f9.16

		$url = new Url('https://lightship.dev/account/geospatial-browser/');
		$params = [
			$coordinates->getLat(),
			$coordinates->getLon(),
			round($zoom ?? 12.66, 2), // As of 2025-10-23 web is behaving weird by centering to completely different venue when it has more than 2 digits or if zoom is missing
			$meshId ?? '',
			$guid ?? '',
		];
		$glue = ',';
		$url->path .= trim(implode($glue, $params), $glue);
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
