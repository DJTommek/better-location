<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Config;
use App\MiniCurl\MiniCurl;
use App\Utils\Coordinates;
use App\Utils\Strict;

final class ZniceneKostelyCzService extends AbstractServiceNew
{
	const NAME = 'ZniceneKostely.cz';

	const LINK = 'http://znicenekostely.cz';

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
		}
	}

	public function isValid(): bool
	{
		return (
			$this->url->getDomain(2) === 'znicenekostely.cz' &&
			$this->url->getQueryParameter('load') === 'detail' &&
			Strict::isPositiveInt($this->url->getQueryParameter('id'))
		);
	}

	public function process(): void
	{
		$response = (new MiniCurl($this->url->getAbsoluteUrl()))->allowCache(Config::CACHE_TTL_ZNICENE_KOSTELY_CZ)->run()->getBody();
		if (preg_match('/WGS84 souřadnice objektu: ([0-9.]+)°N, ([0-9.]+)°E/', $response, $matches)) {
			if (Coordinates::isLat($matches[1]) && Coordinates::isLon($matches[2])) {
				$location = new BetterLocation($this->inputUrl, Strict::floatval($matches[1]), Strict::floatval($matches[2]), self::class);
				$this->collection->add($location);
			}
		}
	}
}
