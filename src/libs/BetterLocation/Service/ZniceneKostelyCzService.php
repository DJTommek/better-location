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

	private int $objectId;

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		if ($this->url?->getDomain(2) !== 'znicenekostely.cz') {
			return false;
		}

		// URLs format since year 2025, eg https://www.znicenekostely.cz/objekt/detail/8139
		if (preg_match('/^\/objekt\/detail\/([0-9]+)/', $this->url->getPath(), $matches)) {
			$this->objectId = (int)$matches[1];
			return true;
		}

		// URL format before year 2025, eg http://www.znicenekostely.cz/?load=detail&id=18231
		if ($this->url->getQueryParameter('load') !== 'detail') {
			return false;
		}
		$objectId = $this->url->getQueryParameter('id');
		if (Strict::isPositiveInt($objectId)) {
			$this->objectId = (int)$objectId;
			return true;
		}

		return false;
	}

	public function process(): void
	{
		$url = $this->objectUrl($this->objectId);
		$response = $this->requestor->get($url, Config::CACHE_TTL_ZNICENE_KOSTELY_CZ);
		if (!preg_match('/WGS84 souřadnice objektu: <b>([0-9.]+)°N, ([0-9.]+)°E/', $response, $matches)) {
			return;
		}
		$coords = CoordinatesImmutable::safe($matches[1], $matches[2]);
		if ($coords === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
		$this->collection->add($location);
	}

	private function objectUrl(int $objectId): string
	{
		return 'https://www.znicenekostely.cz/objekt/detail/' . $objectId;
	}
}
