<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\Utils\General;

final class OsmAndService extends AbstractService
{
	const NAME = 'OsmAnd';

	const LINK = 'https://osmand.net';

	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		return self::LINK . sprintf('/go.html?lat=%1$f&lon=%2$f', $lat, $lon);
	}

	public static function parseCoords(string $url): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinates is not available.');
	}

	public static function isValid(string $input): bool
	{
		return false;
	}

	public static function isUrl(string $url): bool
	{
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array(mb_strtolower($parsedUrl['host']), ['osmand.net', 'www.osmand.net'], true) &&
			isset($parsedUrl['path']) &&
			($parsedUrl['path'] === '/go.html' || $parsedUrl['path'] === '/go') &&
			isset($parsedUrl['query']) &&
			isset($parsedUrl['query']['lat']) &&
			isset($parsedUrl['query']['lon']) &&
			preg_match('/^-?[0-9]{1,3}\.[0-9]{1,20}$/', $parsedUrl['query']['lat']) &&
			preg_match('/^-?[0-9]{1,3}\.[0-9]{1,20}$/', $parsedUrl['query']['lon']) &&
			BetterLocation::isLatValid(floatval($parsedUrl['query']['lat'])) &&
			BetterLocation::isLonValid(floatval($parsedUrl['query']['lon']))
		);
	}

	public static function parseUrl(string $url): BetterLocation
	{
		$parsedUrl = General::parseUrl($url);
		return new BetterLocation($url, floatval($parsedUrl['query']['lat']), floatval($parsedUrl['query']['lon']), self::class);
	}

	public static function parseCoordsMultiple(string $input): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinates is not available.');
	}
}
