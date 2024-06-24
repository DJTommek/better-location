<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\MiniCurl\MiniCurl;

final class VojenskoCzService extends AbstractService
{
	const ID = 35;
	const NAME = 'Vojensko.cz';

	const LINK = 'http://www.vojensko.cz';

	public function validate(): bool
	{
		return (
			$this->url &&
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
		$locationElement = $finder->query('//div[@id="detail-text"]//a[text() = "Najít na mapě"]/@href')->item(0);
		if ($locationElement === null) {
			return;
		}

		$mapyCzLocation = MapyCzService::processStatic($locationElement->textContent)->getFirst();
		$location = new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class);

		$objectName = trim($finder->query('//div[@id="detail-text"]//h4')->item(0)->textContent);
		$location->setPrefixMessage(sprintf('<a href="%s" target="_blank">%s %s</a>',
			$this->inputUrl->getAbsoluteUrl(),
			self::NAME,
			$objectName
		));
		$this->collection->add($location);
	}
}
