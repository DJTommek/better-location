<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\NotImplementedException;

final class HereWeGoService extends AbstractService
{
	const LINK = 'https://wego.here.com';
	const LINK_SHARE = 'https://share.here.com';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @see https://developer.here.com/documentation/deeplink-web/dev_guide/topics/key-concepts.html
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-route.html
			return self::LINK_SHARE . sprintf('/r/%1$f,%2$f', $lat, $lon);
		} else { // https://developer.here.com/documentation/deeplink-web/dev_guide/topics/share-location.html
			return self::LINK_SHARE . sprintf('/l/%1$f,%2$f', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool {
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws NotImplementedException
	 */
	public static function parseCoords(string $url): BetterLocation {
		throw new NotImplementedException('Parsing coordinates is not available');
	}

	public static function isUrl(string $url): bool {
		// @TODO not yet implemented
		return false;
	}

	/**
	 * @param string $url
	 * @return array|null
	 * @throws NotImplementedException
	 */
	public static function parseUrl(string $url): ?array {
		throw new NotImplementedException('Parsing URL is not available');
	}

	/**
	 * @param string $input
	 * @return BetterLocation[]
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): array {
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
