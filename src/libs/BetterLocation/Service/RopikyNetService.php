<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;
use Nette\Utils\Arrays;

final class RopikyNetService extends AbstractServiceNew
{
	const NAME = 'Řopíky.net';

	const LINK = 'https://ropiky.net';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
			// @TODO probably could be used mapa.opevneni.cz Example:
			// https://www.ropiky.net/dbase_objekt.php?id=1183840757
			// -> http://www.opevneni.cz/asp/mapred9rop.asp?id=1183840757
			// -> http://mapa.opevneni.cz/?n=48.32574&e=20.23345&z=16&r=10006000000&m=r
		}
	}

	public function isValid(): bool
	{
		return (
			$this->url->getDomain(2) === 'ropiky.net' &&
			Arrays::contains(['/dbase_objekt.php', '/nerop_objekt.php'], $this->url->getPath()) &&
			Strict::isPositiveInt($this->url->getQueryParameter('id'))
		);
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_ROPIKY_NET)->run()->getBody();
		if (preg_match('/<a href=\"(https:\/\/mapy\.cz\/[^"]+)/', $response, $matches)) {
			$mapyCzService = new MapyCzServiceNew($matches[1]);
			if ($mapyCzService->isValid()) {
				$mapyCzService->process();
				if ($mapyCzLocation = $mapyCzService->getCollection()->getFirst()) {
					$this->collection->add(new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class));
				}
			}
		}
	}
}
