<?php declare(strict_types=1);

namespace App\Utils;

use App\BetterLocation\BetterLocation;
use App\Factory;
use App\Icons;
use Tracy\Debugger;

class Ingress
{
	public static function addPortalData(BetterLocation $location): void
	{
		try {
			$portal = Factory::IngressLanchedRu()->getPortalByCoords($location->getLat(), $location->getLon());
			if ($portal === null) {
				return;
			}
			$prefix = $location->getPrefixMessage();
			$prefix .= sprintf(' <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name));
			$location->setInlinePrefixMessage($prefix);
			$prefix .= sprintf(' <a href="%s">%s</a>', $portal->image, Icons::PICTURE);
			$location->setPrefixMessage($prefix);
			if (in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
				$location->setAddress(htmlspecialchars($portal->address));
			}
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
		}
	}
}
