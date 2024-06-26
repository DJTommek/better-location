<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Utils;
use DJTommek\Coordinates\Coordinates;

final class HradyCzService extends AbstractService
{
	const ID = 34;
	const NAME = 'Hrady.cz';

	const LINK = 'https://www.hrady.cz';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'hrady.cz' &&
			// path has always at least two words joined with dash
			preg_match('/^\/[a-z0-9]+-[a-z0-9]+/', $this->url->getPath())
		);
	}

	public function process(): void
	{
		$paths = explode('/', $this->url->getPath());
		$this->url->setPath($paths[1]);
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_HRADY_CZ);

		$dom = Utils::domFromUTF8($response);
		$ldJson = Utils::parseLdJson($dom);
		if ($ldJson === null) {
			return;
		}

		assert($ldJson->geo->{'@type'} === 'GeoCoordinates');
		// Coordinates are provided as string, so convert and validate them before providing them into BetterLocation
		$coords = new Coordinates($ldJson->geo->latitude, $ldJson->geo->longitude);

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
		$this->updatePrefixMessage($location, $ldJson->name);
		$this->collection->add($location);
	}

	private function updatePrefixMessage(BetterLocation $location, string $place): void
	{
		if ($this->inputUrl->isEqual($this->url)) {
			$prefix = sprintf('<a href="%s">%s %s</a>', $this->inputUrl, self::NAME, $place);
		} else {
			$prefix = sprintf('<a href="%s">%s</a> <a href="%s">%s</a>', $this->inputUrl, self::NAME, $this->url, $place);
		}
		$location->setPrefixMessage($prefix);
	}
}
