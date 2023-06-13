<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Utils;
use DJTommek\Coordinates\Coordinates;

final class KudyZNudyCzService extends AbstractService
{
	const ID = 48;
	const NAME = 'kudyznudy.cz';

	const LINK = 'https://kudyznudy.cz';

	const TYPE_ACTIVITY = 'activity';
	const TYPE_EVENT = 'event';

	public function isValid(): bool
	{
		if (
			$this->url
			&& $this->url->getDomain(2) === 'kudyznudy.cz'
		) {
			$path = $this->url->getPath();
			if (str_starts_with($path, '/aktivity/')) {
				$this->data->type = self::TYPE_ACTIVITY;
				return true;
			}
			if (str_starts_with($path, '/akce/')) {
				$this->data->type = self::TYPE_EVENT;
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url))
			->allowCache(Config::CACHE_TTL_KUDY_Z_NUDY_CZ)
			->run()
			->getBody();
		$dom = Utils::domFromUTF8($response);
		$element = $dom->getElementById('szn-map');
		if ($element === null) {
			return;
		}
		$coords = Coordinates::safe(
			$element->getAttribute('data-lat'),
			$element->getAttribute('data-lon'),
		);
		if ($coords === null) {
			return;
		}

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class, $this->data->type);

		$ldJson = Utils::parseLdJson($dom);

		if ($ldJson !== null) {
			$location->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $this->input, self::NAME, htmlspecialchars($ldJson->location->name)));
			$location->setAddress($this->addressFromLdJson($ldJson));
		}

		$this->collection->add($location);
	}

	private function addressFromLdJson(\stdClass $ldJson): string
	{
		$address = $ldJson->location->address;
		return sprintf(
			'%s, %s, %s',
			$address->streetAddress,
			$address->addressLocality,
			$address->addressCountry,
		);
	}

	public static function getConstants(): array
	{
		return [
			self::TYPE_ACTIVITY,
			self::TYPE_EVENT,
		];
	}
}
