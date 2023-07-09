<?php declare(strict_types=1);

namespace App\BetterLocation\Service\VodniMlynyCz;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\AbstractService;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Strict;

final class VodniMlynyCzService extends AbstractService
{
	public const ID = 32;
	public const NAME = 'vodnimlyny.cz';
	public const LINK = 'http://vodnimlyny.cz';
	/**
	 * Database of all available estates on website
	 */
	private const ESTATE_LIST_URL = 'https://www.vodnimlyny.cz/en/mlyny/estates/map/?do=getEstates';

	public function isValid(): bool
	{
		if (
			$this->url
			&& $this->url->getDomain(2) === 'vodnimlyny.cz'
			&& preg_match('/^\/[a-z]+\/mlyny\/estates\/detail\/([0-9]+)/', $this->url->getPath(), $matches)
		) {
			$this->data->id = Strict::intval($matches[1]);
			return true;
		}
		return false;
	}

	public function process(): void
	{
		$estates = $this->getEstates();
		$estate = $this->searchInEstates($estates, $this->data->id);

		if ($estate === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $estate->lat, $estate->lng, self::class);
		$location->setPrefixTextInLink($estate->name);
		$this->collection->add($location);
	}

	/**
	 * Get list of available estates on vodnimlyny.cz
	 *
	 * @return array<int, \stdClass> Returns raw JSON response (not parsed as array of Estates for performance reasons)
	 */
	private function getEstates(): array
	{
		$client = new MiniCurl(self::ESTATE_LIST_URL);
		$client->allowCache(Config::CACHE_TTL_VODNIMLYNY_CZ);

		return $client->run()->getBodyAsJson();
	}

	/**
	 * @param array<\stdClass|Estate> $estates
	 */
	private function searchInEstates(array $estates, int $id): ?Estate
	{
		foreach ($estates as $estate) {
			if ($estate->id === $id) {
				return Estate::fromResponse($estate);
			}
		}
		return null;
	}
}
