<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\MiniCurl\MiniCurl;

final class VojenskoCzService extends AbstractService
{
	const ID = 35;
	const NAME = 'Vojensko.cz';

	const LINK = 'http://www.vojensko.cz';

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getScheme() === 'http' && // page is not working on https
			$this->url->getDomain(2) === 'vojensko.cz' &&
			mb_strlen($this->url->getPath()) > 1 // not root
		);
	}

	public function process(): void
	{
		// Do not allow caching, because it would be stored with bad encoding. Needs to be converted first
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))
			->allowCache(0) // do not allow caching, there is non-UTF-8 encoding. Must be decoded first
			->allowAutoConvertEncoding(false) // default mb_convert_encoding is not working for this website
			->run()
			->getBody();
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
		if ($locationElement = $finder->query('//div[@id="detail"]//a[text() = "Najít na mapě"]/@href')->item(0)) {
			$mapyCzLocation = MapyCzService::processStatic($locationElement->textContent)->getFirst();
			$location = new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class);
			$location->setPrefixMessage(sprintf('<a href="%s" target="_blank">%s %s</a>',
				$this->inputUrl->getAbsoluteUrl(),
				self::NAME,
				trim($finder->query('//div[@id="detail"]//h4/text()')->item(0)->textContent)
			));
			$this->collection->add($location);
		}
	}
}
