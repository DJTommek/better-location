<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

final class DrobnePamatkyCzService extends AbstractService
{
	const ID = 16;
	const NAME = 'DrobnePamatky.cz';

	const LINK = 'https://www.drobnepamatky.cz';

	const PATH_REGEX = '/^\/node\/[0-9]+$/';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/blizko?km[latitude]=%1$f&km[longitude]=%2$f&km[search_distance]=5&km[search_units]=km', $lat, $lon);
		}
	}

	public function isValid(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'drobnepamatky.cz' &&
			preg_match(self::PATH_REGEX, $this->url->getPath(), $matches)
		);
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->input))->allowCache(Config::CACHE_TTL_DROBNE_PAMATKY_CZ)->run()->getBody();
		if (!preg_match('/<meta\s+name="geo\.position"\s*content="([0-9.]+);\s*([0-9.]+)\s*"/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
		$location = new BetterLocation($this->input, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class);
		if (preg_match('/<h1[^>]+>(.+)<\/h1>/s', $response, $titleMatches)) {
			$location->setDescription(trim(strip_tags($titleMatches[1])));
		}
		$this->collection->add($location);
	}
}
