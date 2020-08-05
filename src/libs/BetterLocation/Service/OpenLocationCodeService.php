<?php

declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use OpenLocationCode\OpenLocationCode;

final class OpenLocationCodeService extends AbstractService
{
	const LINK = 'https://plus.codes/';

	const DEFAULT_CODE_LENGTH = 12;

	const RE = '/^([23456789C][23456789CFGHJMPQRV][23456789CFGHJMPQRVWX]{6}\+[23456789CFGHJMPQRVWX]{2,3})$/i';
	const RE_IN_STRING = '/(^|\s)([23456789C][23456789CFGHJMPQRV][23456789CFGHJMPQRVWX]{6}\+[23456789CFGHJMPQRVWX]{2,3})(\s|$)/i';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws \Exception
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new \InvalidArgumentException('Drive link is not implemented.');
		} else {
			$plusCode = OpenLocationCode::encode($lat, $lon, self::DEFAULT_CODE_LENGTH);
			return self::LINK . $plusCode;
		}
	}

	public static function isValid(string $input): bool {
		return self::isUrl($input) || self::isCode($input);
	}

	/**
	 * @param string $plusCodeInput
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $plusCodeInput): BetterLocation {
		if (self::isUrl($plusCodeInput)) {
			$coords = self::parseUrl($plusCodeInput);
			return new BetterLocation(
				$coords[0],
				$coords[1],
				sprintf('<a href="%s">OLC</a>', $plusCodeInput) // @TODO would be nice to return detected OLC code
			);
		} else if (self::isCode($plusCodeInput)) {  // at least two characters, otherwise it is probably /s/hort-version of link
			$coords = OpenLocationCode::decode($plusCodeInput);
			return new BetterLocation(
				$coords['latitudeCenter'],
				$coords['longitudeCenter'],
				sprintf('<a href="%s">OLC</a> <code>%s</code>: ', self::getLink($coords['latitudeCenter'], $coords['longitudeCenter']), $plusCodeInput),
			);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from OpenLocationCode "%s".', $plusCodeInput));
		}
	}

	/**
	 * @param string $url
	 * @return bool
	 */
	public static function isUrl(string $url): bool {
		// https://plus.codes/8FXP74WG+XHW
		if (substr($url, 0, mb_strlen(self::LINK)) === self::LINK) {
			$plusCode = str_replace(self::LINK, '', $url);
			return self::isValid($plusCode);
		}
		return false;
	}

	/**
	 * @param string $plusCode
	 * @return bool
	 *
	 */
	public static function isCode(string $plusCode): bool {
		return OpenLocationCode::isValid($plusCode);
	}

	/**
	 * @TODO query parameters should have higher priority than hash params
	 *
	 * @param string $url
	 * @return array|null
	 * @throws \Exception
	 */
	public static function parseUrl(string $url): ?array {
		$plusCode = str_replace(self::LINK, '', $url);
		$coords = OpenLocationCode::decode($plusCode);
		return [
			$coords['latitudeCenter'],
			$coords['longitudeCenter'],
		];
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
