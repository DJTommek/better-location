<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;

final class SumavaCzService extends AbstractService
{
	const NAME = 'Šumava.cz';

	const LINK = 'http://www.sumava.cz/';

	const TYPE_ACCOMODATION = 'Accomodation';
	const TYPE_PLACE = 'Place';

	public static function getConstants(): array
	{
		return [
			self::TYPE_ACCOMODATION,
			self::TYPE_PLACE,
		];
	}

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public function isValid(): bool
	{
		if (
			$this->url &&
			$this->url->getDomain(2) === 'sumava.cz'
		) {
			if (preg_match('/\/objekt\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_ACCOMODATION;
				return true;
			} else if (preg_match('/\/objekt_az\/([0-9]+)/', $this->url->getPath(), $matches)) {
				$this->data->type = self::TYPE_PLACE;
				return true;
			}
		}
		return false;
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_SUMAVA_CZ)->run()->getBody();
		if (preg_match('/SMap\.Coords\.fromWGS84\(([0-9.]+),([0-9.]+)/', $response, $matches)) {
			$coords = new Coordinates($matches[2], $matches[1]);
			$location = new BetterLocation($this->inputUrl, $coords->getLat(), $coords->getLon(), self::class, $this->data->type);
			$location->setPrefixMessage(sprintf('<a href="%s">%s</a>', $this->inputUrl, self::NAME));
			$this->collection->add($location);
		}
	}
}
