<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\General;
use Tracy\Debugger;
use Tracy\ILogger;

final class RopikyNetService extends AbstractService
{
	const NAME = 'Řopíky.net';

	const LINK = 'https://ropiky.net';

	/**
	 * @param float $lat
	 * @param float $lon
	 * @param bool $drive
	 * @return string
	 * @throws NotSupportedException
	 */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not implemented.');
			// @TODO probably could be used mapa.opevneni.cz Example:
			// https://www.ropiky.net/dbase_objekt.php?id=1183840757
			// -> http://www.opevneni.cz/asp/mapred9rop.asp?id=1183840757
			// -> http://mapa.opevneni.cz/?n=48.32574&e=20.23345&z=16&r=10006000000&m=r
		}
	}

	public static function isValid(string $url): bool
	{
		return self::isUrl($url);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException
	 */
	public static function parseCoords(string $url): BetterLocation
	{
		$coords = self::parseUrl($url);
		if ($coords) {
			return new BetterLocation($url, $coords[0], $coords[1], self::class);
		} else {
			throw new InvalidLocationException(sprintf('Unable to get coords from Ropiky.net link %s.', $url));
		}
	}

	public static function isUrl(string $url): bool
	{
		$url = mb_strtolower($url);
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array($parsedUrl['host'], ['ropiky.net', 'www.ropiky.net']) &&
			isset($parsedUrl['path']) &&
			in_array($parsedUrl['path'], ['/dbase_objekt.php', '/nerop_objekt.php'], true) &&
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['id']) &&
			preg_match('/^[0-9]+$/', $parsedUrl['query']['id'])
		);
	}

	public static function parseUrl(string $url): ?array
	{
		try {
			$response = General::fileGetContents($url, [
				CURLOPT_CONNECTTIMEOUT => 5,
				CURLOPT_TIMEOUT => 5,
			]);
		} catch (\Throwable $exception) {
			Debugger::log($exception, ILogger::DEBUG);
			return null;
		}
		if (!preg_match('/<a href=\"(https:\/\/mapy\.cz\/[^"]+)/', $response, $matches)) {
			Debugger::log($response, ILogger::DEBUG);
			throw new InvalidLocationException(sprintf('Coordinates on Ropiky.net page are missing.'));
		}
		$mapyCzUrl = $matches[1];
		if (MapyCzService::isNormalUrl($mapyCzUrl) === false) {
			throw new InvalidLocationException(sprintf('Parsed Mapy.cz URL from "%s" is not valid.', $url));
		}
		$mapyCzLocation = MapyCzService::parseUrl($mapyCzUrl);
		return [
			$mapyCzLocation->getLat(),
			$mapyCzLocation->getLon(),
		];
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
