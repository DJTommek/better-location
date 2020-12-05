<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Factory;
use App\Icons;
use App\IngressMosaic\Client;
use App\IngressMosaic\Types\MosaicType;
use App\Utils\General;

final class IngressMosaicService extends AbstractService
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

	public static function isValid(string $url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['ingressmosaic.com', 'www.ingressmosaic.com', 'ingressmosaik.com', 'www.ingressmosaik.com'], true) &&
			isset($parsedUrl['path']) &&
			preg_match(self::RE_PATH, $parsedUrl['path'])
		);
	}

	public static function parseUrl(string $url): ?BetterLocation
	{
		if ($mosaic = self::loadMosaicPage($url)) {
			$betterLocation = new BetterLocation($url, $mosaic->startLat, $mosaic->startLon, self::class);

			$prefix = $betterLocation->getPrefixMessage();
			$betterLocation->setInlinePrefixMessage(sprintf('%s %s', $prefix, $mosaic->name));
			$prefix .= sprintf(' <a href="%s">%s</a> <a href="%s">%s</a>', $mosaic->url, $mosaic->name, $mosaic->image, Icons::PICTURE);
			$betterLocation->setPrefixMessage($prefix);

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
				$betterLocation->setAddress($portal->address);
				$description .= PHP_EOL . sprintf('Start portal: <a href="%s">%s</a> <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name), $portal->image, Icons::PICTURE);
			}
			$betterLocation->setDescription($description);

			return $betterLocation;
		} else {
			return null;
		}
	}

	public static function parseCoords(string $url): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinate is not supported.');
	}

	public static function parseCoordsMultiple(string $url): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinate is not supported.');
	}

	private static function loadMosaicPage(string $url): ?MosaicType
	{
		$parsedUrl = General::parseUrl($url);
		if (preg_match(self::RE_PATH, $parsedUrl['path'], $matches)) {
			return Factory::IngressMosaic()->loadMosaic((int)$matches[1]);
		} else {
			return null;
		}
	}
}
