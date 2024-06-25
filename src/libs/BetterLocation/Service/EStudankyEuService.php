<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Utils;

final class EStudankyEuService extends AbstractService
{
	const ID = 33;
	const NAME = 'estudanky.eu';

	const LINK = 'https://estudanky.eu';

	public function __construct(
		private readonly Requestor $requestor,
		private readonly MapyCzService $mapyCzService,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'estudanky.eu' &&
			preg_match('/^\/([0-9]+)/', $this->url->getPath())
		);
	}

	public function process(): void
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_ESTUDANKY_EU);
		$dom = Utils::domFromUTF8($response);
		$finder = new \DOMXPath($dom);
		$mapyczLink = $finder->query('//div/div/ul/li/a[contains(text(),"Mapy.CZ")]/@href')->item(0)->textContent;
		if ($mapyczLink === null) {
			return;
		}
		$this->mapyCzService->setInput($mapyczLink);
		$this->mapyCzService->validate();
		$this->mapyCzService->process();
		$mapyCzLocation = $this->mapyCzService->getCollection()->getFirst();
		$location = new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class);
		if ($placeName = self::getPlaceName($finder)) {
			$location->setPrefixMessage(sprintf('<a href="%s">%s - %s</a>', $this->inputUrl, self::NAME, $placeName));
		}
		$this->collection->add($location);
	}

	private static function getPlaceName(\DOMXPath $finder): ?string
	{
		$placeNameRaw = $finder->query('//h1/text()')->item(0)->textContent;
		if (str_starts_with($placeNameRaw, 'studna bez jména (') || str_starts_with($placeNameRaw, 'jiný vodní zdroj bez jména (')) {
			return null;
		} else {
			return preg_replace('/ \([0-9]+\)$/', '', $placeNameRaw);
		}
	}
}
