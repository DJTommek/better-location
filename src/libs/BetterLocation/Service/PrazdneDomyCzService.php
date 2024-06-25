<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;

final class PrazdneDomyCzService extends AbstractService
{
	const ID = 39;
	const NAME = 'Prazdnedomy.cz';

	const LINK = 'https://prazdnedomy.cz';

	public function __construct(
		private readonly Requestor $requestor,
		private readonly MapyCzService $mapyCzService,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'prazdnedomy.cz' &&
			preg_match('/\/domy\/objekty\/detail\/([0-9]+)/', $this->url->path)
		);
	}

	public function process(): void
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_PRAZDNE_DOMY);
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
		$mapyczLink = $finder->query('//div[@class="estate-info-box"]/div/a[contains(text(),"mapa")]/@href')->item(0)->textContent;
		if ($mapyczLink === null) {
			return;
		}
		$this->mapyCzService->setInput($mapyczLink);
		$this->mapyCzService->validate();
		$this->mapyCzService->process();
		$mapyCzLocation = $this->mapyCzService->getCollection()->getFirst();
		$location = new BetterLocation($this->url, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class);

		$placeName = $finder->query('//h1/text()')->item(0)->textContent;
		$location->setPrefixMessage(sprintf('<a href="%s">%s - %s</a>', $this->inputUrl, self::NAME, $placeName));

		$this->collection->add($location);
	}
}
