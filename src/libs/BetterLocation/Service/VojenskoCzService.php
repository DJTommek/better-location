<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;

final class VojenskoCzService extends AbstractService
{
	const ID = 35;
	const NAME = 'Vojensko.cz';

	const LINK = 'http://www.vojensko.cz';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'vojensko.cz' &&
			// Examples:
			// '/objekty-csla/sekce-00031-kasarna-a-objekty-csla/polozka-03180-vu-5849-jachymov-vrsek'
			// '/ruzne/sekce-00058-pristroje-nastroje-zbrane/polozka-04356-zavora-ippen-pavluv-studenec'
			preg_match('/^\/[^\/]+\/sekce-[0-9]+[^\/]+\/polozka-[0-9]+/', $this->url->getPath())
		);
	}

	public function process(): void
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_VOJENSKO_CZ);
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
			$objectName,
		));
		$this->collection->add($location);
	}
}
