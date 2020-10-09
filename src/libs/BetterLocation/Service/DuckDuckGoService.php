<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\NotImplementedException;

final class DuckDuckGoService extends AbstractService
{
	const NAME = 'DuckDuckGo';

	const LINK = 'https://duckduckgo.com';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotImplementedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new NotImplementedException('Drive link is not implemented.');
		} else {
			return self::LINK . sprintf('/?q=%1$f,%2$f&iaxm=maps', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool {
		return false;
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoords(string $url): BetterLocation {
		throw new NotImplementedException('Parsing coordinates is not available.');
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
