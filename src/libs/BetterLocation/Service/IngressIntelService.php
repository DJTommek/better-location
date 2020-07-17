<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;

final class IngressIntelService extends AbstractService
{
	const LINK = 'https://intel.ingress.com/intel';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new \InvalidArgumentException('Drive link is not implemented.');
		} else {
			return sprintf(self::LINK . '%s?ll=%1$f,%2$f&pll=%1$f,%2$f', $lat, $lon);
		}
	}

	public static function isValid(string $url): bool {
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url) {
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($coords[0], $coords[1], sprintf('<a href="%s">Intel</a>', $url));
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from Ingress Intel link "%s".', $url));
		}
	}

	public static function isUrl(string $url): bool {
		return substr($url, 0, mb_strlen(self::LINK)) === self::LINK;
	}

	/**
	 * @param string $url
	 * @return array|null
	 */
	public static function parseUrl(string $url): ?array {
		$paramsString = explode('?', $url);
		if (count($paramsString) === 2) {
			parse_str($paramsString[1], $params);
			if (isset($params['pll'])) {
				$coords = explode(',', $params['pll']);
			} else if (isset($params['ll'])) {
				$coords = explode(',', $params['ll']);
			} else {
				return null;
			}
			return [
				floatval($coords[0]),
				floatval($coords[1]),
			];
		} else {
			return null;
		}
	}
}
