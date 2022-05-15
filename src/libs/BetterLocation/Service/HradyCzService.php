<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Utils;

final class HradyCzService extends AbstractService
{
	const ID = 34;
	const NAME = 'Hrady.cz';

	const LINK = 'https://www.hrady.cz';

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'hrady.cz' &&
			// path has always at least three words joined with dash
			preg_match('/^\/[a-z0-9]+-[a-z0-9]+-[a-z0-9]+/', $this->url->getPath())
		);
	}

	public function process(): void
	{
		$paths = explode('/', $this->url->getPath());
		$this->url->setPath($paths[1]);
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_HRADY_CZ)->run()->getBody();
		if ($coords = Utils::findMapyCzApiCoords($response)) {
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class);
			$this->updatePrefixMessage($location, $response);
			$this->collection->add($location);
		}
	}

	private function updatePrefixMessage(BetterLocation $location, string $response): void
	{
		$place = self::getPlaceName($response);
		if ($this->url->isEqual($this->inputUrl)) {
			$prefix = sprintf('<a href="%s">%s %s</a>', $this->inputUrl, self::NAME, $place);
		} else {
			$prefix = sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::NAME, $this->url, $place);
		}
		$location->setPrefixMessage($prefix);
	}

	private static function getPlaceName($response): string
	{
		$dom = new \DOMDocument();
		@$dom->loadHTML($response);
		$finder = new \DOMXPath($dom);
		return trim($finder->query('//h1/a/text()')->item(1)->textContent); // first item is <span> with icon
	}
}
