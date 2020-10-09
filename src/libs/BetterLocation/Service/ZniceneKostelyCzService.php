<?php declare(strict_types=1);

namespace BetterLocation\Service;

use BetterLocation\BetterLocation;
use BetterLocation\BetterLocationCollection;
use BetterLocation\Service\Exceptions\InvalidLocationException;
use BetterLocation\Service\Exceptions\NotImplementedException;
use BetterLocation\Service\Exceptions\NotSupportedException;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

final class ZniceneKostelyCzService extends AbstractService
{
	const NAME = 'ZniceneKostely.cz';

	const LINK = 'http://znicenekostely.cz';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotSupportedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string {
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
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
	public static function parseCoords(string $url): BetterLocation {
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($url, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from %s link %s.', self::NAME, $url));
		}
	}

	public static function isUrl(string $url): bool {
		$url = mb_strtolower($url);
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array($parsedUrl['host'], ['znicenekostely.cz', 'www.znicenekostely.cz']) &&
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['load']) &&
			$parsedUrl['query']['load'] === 'detail' &&
			isset($parsedUrl['query']['id']) &&
			preg_match('/^[0-9]+$/', $parsedUrl['query']['id'])
		);
	}

	public static function parseUrl(string $url): ?array {
		try {
			$response = General::fileGetContents($url, [
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 5,
			]);
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
		if (!preg_match('/WGS84 souřadnice objektu: ([0-9.]+)°N, ([0-9.]+)°E/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on %s page are missing.', self::NAME));
		}
		return [
			floatval($matches[1]),
			floatval($matches[2]),
		];
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
