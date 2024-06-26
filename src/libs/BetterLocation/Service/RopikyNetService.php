<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;
use Nette\Utils\Arrays;

final class RopikyNetService extends AbstractService
{
	const ID = 28;
	const NAME = 'Řopíky.net';

	const LINK = 'https://ropiky.net';

	public function __construct(
		private readonly MapyCzService $mapyCzService,
	) {
	}

	public function validate(): bool
	{
		return (
			$this->url &&
			$this->url->getDomain(2) === 'ropiky.net' &&
			Arrays::contains(['/dbase_objekt.php', '/nerop_objekt.php'], $this->url->getPath()) &&
			Strict::isPositiveInt($this->url->getQueryParameter('id'))
		);
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_ROPIKY_NET)->run()->getBody();
		if (preg_match('/<a href=\"(https:\/\/mapy\.cz\/[^"]+)/', $response, $matches)) {
			$this->mapyCzService->setInput($matches[1]);
			if ($this->mapyCzService->validate()) {
				$this->mapyCzService->process();
				if ($mapyCzLocation = $this->mapyCzService->getCollection()->getFirst()) {
					$this->collection->add(new BetterLocation($this->inputUrl, $mapyCzLocation->getLat(), $mapyCzLocation->getLon(), self::class));
				}
			}
		}
	}
}
