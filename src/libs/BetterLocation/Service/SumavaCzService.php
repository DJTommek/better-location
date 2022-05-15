<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Utils;
use App\Utils\StringUtils;
use Tracy\Debugger;

final class SumavaCzService extends AbstractService
{
	const ID = 29;
	const NAME = 'Šumava.cz';

	const LINK = 'http://www.sumava.cz';

	const TYPE_ACCOMODATION = 'Accomodation';
	const TYPE_PLACE = 'Place';
	const TYPE_GALLERY = 'Gallery';
	const TYPE_COMPANY = 'Company';

	public static function getConstants(): array
	{
		return [
			self::TYPE_ACCOMODATION,
			self::TYPE_PLACE,
			self::TYPE_GALLERY,
			self::TYPE_COMPANY,
		];
	}

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'sumava.cz'
		) {
			if (preg_match('/^\/objekt\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_ACCOMODATION;
				return true;
			} else if (preg_match('/^\/objekt_az\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_PLACE;
				return true;
			} else if (preg_match('/^\/firma\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_COMPANY;
				return true;
			} else if (preg_match('/^\/galerie_sekce\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_GALLERY;
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_SUMAVA_CZ)->run()->getBody();
		if ($this->data->type === self::TYPE_GALLERY) {
			$this->processGallery($response);
		} else {
			if ($coords = Utils::findMapyCzApiCoords($response)) {
				$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, $this->data->type);
				$location->setPrefixMessage(sprintf('<a href="%s">%s</a>', $this->inputUrl, self::NAME));
				$this->collection->add($location);
			}
		}
	}

	/**
	 * Gallery doesn't have specific location but it is paired with one or more locations, process them all.
	 */
	private function processGallery(string $response)
	{
		$doc = new \DOMDocument();
		$doc->loadHTML($response);

		$xpath = new \DOMXPath($doc);
		foreach ($xpath->query('//div[@id=\'item_1\']//h3/a') as $linkElement) {
			assert($linkElement instanceof \DOMElement);
			$linkPath = $linkElement->getAttribute('href');
			if (StringUtils::startWith($linkPath, '/objekt_az/')) {
				$fullLink = self::LINK . $linkPath;
				$service = new SumavaCzService($fullLink);
				if ($service->isValid()) {
					$service->process();
					$collection = $service->getCollection();
					assert(count($collection) === 1);
					$location = $collection->getFirst();
					$prefix = sprintf('<a href="%s">%s gallery</a> - <a href="%s">%s</a>', $this->url->getAbsoluteUrl(), self::NAME, $fullLink, $linkElement->textContent);
					$location->setPrefixMessage($prefix);
					$this->collection->add($location);
				}
			} else {
				// mostly advertisement for random accomodation, not really related to this gallery
				Debugger::log(sprintf('Ignoring path "%s" (text: "%s")', $linkPath, $linkElement->textContent), Debugger::DEBUG);
			}
		}
		$this->collection->filterTooClose = false;
	}
}
