<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Factory;
use App\Icons;
use App\IngressMosaic\Client;
use App\Utils\Strict;
use Nette\Utils\Arrays;

final class IngressMosaicService extends AbstractServiceNew
{
	const NAME = 'IngressMosaic';
	const LINK = Client::LINK;
	const RE_PATH = '/^\/mosaic\/([0-9]+)$/';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public function isValid(): bool
	{
		if (
			Arrays::contains(['ingressmosaic.com', 'ingressmosaik.com'], $this->url->getDomain(2)) &&
			preg_match(self::RE_PATH, $this->url->getPath(), $matches)
		) {
			$this->data->mosaidId = Strict::intval($matches[1]);
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$mosaic = Factory::IngressMosaic()->loadMosaic($this->data->mosaicId);
		$location = new BetterLocation($this->inputUrl->getAbsoluteUrl(), $mosaic->startLat, $mosaic->startLon, self::class);

		$prefix = $location->getPrefixMessage();
		$location->setInlinePrefixMessage(sprintf('%s %s', $prefix, $mosaic->name));
		$prefix .= sprintf(' <a href="%s">%s</a> <a href="%s">%s</a>', $mosaic->url, $mosaic->name, $mosaic->image, Icons::PICTURE);
		$location->setPrefixMessage($prefix);

		$description = sprintf('%d missions, %d/%d portals/unique, %.1F km, %s',
			$mosaic->missionsTotal,
			$mosaic->portalsTotal,
			$mosaic->portalsUnique,
			$mosaic->distanceTotal / 1000,
			$mosaic->byFootTotal->format('%hh %im'),
		);
		if ($mosaic->nonstop === true) {
			$description .= ', 24/7';
		}
		if ($mosaic->status !== 100) {
			$description = sprintf('%s %d%% online, %s', Icons::WARNING, $mosaic->status, $description);
		}

		$ingressApi = Factory::IngressLanchedRu();
		if ($portal = $ingressApi->getPortalByCoords($mosaic->startLat, $mosaic->startLon)) {
			$location->setAddress($portal->address);
			$description .= PHP_EOL . sprintf('Start portal: <a href="%s">%s</a> <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name), $portal->image, Icons::PICTURE);
		}
		$location->setDescription($description);

		$this->collection->add($location);
	}
}
