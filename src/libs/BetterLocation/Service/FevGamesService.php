<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\Factory;
use App\Icons;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;
use Tracy\Debugger;

final class FevGamesService extends AbstractService
{
	const NAME = 'FevGames';

	const LINK = 'https://fevgames.net';

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
		}
	}

	public function isValid(): bool
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
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_FEVGAMES)->run()->getBody();
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		foreach ($dom->getElementsByTagName('a') as $linkEl) {
			$link = $linkEl->getAttribute('href');
			$intelService = new IngressIntelService($link);
			if ($intelService->isValid()) {
				$data = $intelService->getData();
				if ($data->portalCoord) {
					$location = new BetterLocation($this->inputUrl, $data->portalCoordLat, $data->portalCoordLon, self::class);
					$eventName = $dom->getElementsByTagName('h2')->item(0)->textContent;
					$location->setPrefixMessage(sprintf('<a href="%s">%s</a>', $this->inputUrl, htmlentities($eventName)));
					self::addPortalData($location);
					$this->collection->add($location);
				}
			}
		}
	}

	private static function addPortalData(BetterLocation $location): void
	{
		try {
			$portal = Factory::IngressLanchedRu()->getPortalByCoords($location->getLat(), $location->getLon());
		} catch (\Throwable $exception) {
			Debugger::log($exception, Debugger::EXCEPTION);
			return;
		}
		if ($portal) {
			$location->setDescription(sprintf('Registration portal: <a href="%s">%s</a> <a href="%s">%s</a>', $portal->getIntelLink(), htmlspecialchars($portal->name), $portal->image, Icons::PICTURE));
			if (in_array($portal->address, ['', 'undefined', '[Unknown Location]'], true) === false) { // show portal address only if it makes sense
				$location->setAddress(htmlspecialchars($portal->address));
			}
		}
	}
}
