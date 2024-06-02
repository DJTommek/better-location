<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\BetterLocation\ServicesManager;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Strict;
use Tracy\Debugger;
use Tracy\ILogger;

final class DrobnePamatkyCzService extends AbstractService
{
	const ID = 16;
	const NAME = 'DrobnePamatky.cz';

	const LINK = 'https://www.drobnepamatky.cz';

	const PATH_REGEX = '/^\/node\/[0-9]+$/';

	public const TAGS = [
		ServicesManager::TAG_GENERATE_OFFLINE,
		ServicesManager::TAG_GENERATE_LINK_SHARE,
	];

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public static function getLink(float $lat, float $lon, bool $drive = false, array $options = []): ?string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			return self::LINK . sprintf('/blizko?km[latitude]=%1$F&km[longitude]=%2$F&km[search_distance]=5&km[search_units]=km', $lat, $lon);
		}
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'drobnepamatky.cz' &&
			preg_match(self::PATH_REGEX, $this->url->getPath(), $matches)
		);
	}

	public function process(): void
	{
		$body = $this->requestor->get($this->url, Config::CACHE_TTL_DROBNE_PAMATKY_CZ);

		if (!preg_match('/<meta\s+name="geo\.position"\s*content="([0-9.]+);\s*([0-9.]+)\s*"/', $body, $matches)) {
			Debugger::log($body, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
		$location = new BetterLocation($this->input, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class);
		if (preg_match('/<h1[^>]+>(.+)<\/h1>/s', $body, $titleMatches)) {
			$location->setDescription(trim(strip_tags($titleMatches[1])));
		}
		$this->collection->add($location);
	}
}
