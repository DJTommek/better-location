<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Strict;
use DJTommek\Coordinates\CoordinatesImmutable;

final class ZniceneKostelyCzService extends AbstractService
{
	const ID = 53;
	const NAME = 'ZniceneKostely.cz';

	const LINK = 'http://znicenekostely.cz';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'znicenekostely.cz' &&
			$this->url->getQueryParameter('load') === 'detail' &&
			Strict::isPositiveInt($this->url->getQueryParameter('id'))
		);
	}

	public function process(): void
	{
		$response = $this->requestor->get($this->url, Config::CACHE_TTL_ZNICENE_KOSTELY_CZ);
		if (!preg_match('/WGS84 souřadnice objektu: ([0-9.]+)°N, ([0-9.]+)°E/', $response, $matches)) {
			return;
		}
		$coords = CoordinatesImmutable::safe($matches[1], $matches[2]);
		if ($coords === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
		$this->collection->add($location);
	}
}
