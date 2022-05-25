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
	public static function addPortalData(BetterLocation $location, ?PortalType $portal = null): void
	{
		try {
			if ($portal === null) {
				$portal = Factory::IngressLanchedRu()->getPortalByCoords($location->getLat(), $location->getLon());
			}
			if ($portal === null) {
				return;
			}
			$prefix = $location->getPrefixMessage();
			$prefix .= sprintf(' <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name));
			$location->setInlinePrefixMessage($prefix);
			$prefix .= sprintf(' <a href="%s">%s</a>', $portal->image, Icons::PICTURE);
			$prefix .= sprintf(' <a href="%s">%s</a>', Ingress::generatePrimePortalLink($portal->guid, $portal->lat, $portal->lng), Icons::MOBILE_PHONE);
			$location->setPrefixMessage($prefix);
			if (in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
				$location->setAddress(htmlspecialchars($portal->address));
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
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
		$url->setQueryParameter('ofl', sprintf('https://intel.ingress.com/intel?pll=%F,%F', $lat, $lon));
		return $url;
	}
}
