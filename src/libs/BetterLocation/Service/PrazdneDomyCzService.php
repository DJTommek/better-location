<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;

final class PrazdneDomyCzService extends AbstractService
{
	const ID = 39;
	const NAME = 'Prazdnedomy.cz';

	const LINK = 'https://prazdnedomy.cz';

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
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_PRAZDNE_DOMY)->run()->getBody();
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
		$mapyczLink = $finder->query('//div[@class="estate-info-box"]/div/a[contains(text(),"mapa")]/@href')->item(0)->textContent;
		$mapyCzLocation = MapyCzService::processStatic($mapyczLink)->getFirst();
		$location = new BetterLocation($this->url, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class);

		$placeName = $finder->query('//h1/text()')->item(0)->textContent;
		$location->setPrefixMessage(sprintf('<a href="%s">%s - %s</a>', $this->inputUrl, self::NAME, $placeName));

		$this->collection->add($location);
	}
}
