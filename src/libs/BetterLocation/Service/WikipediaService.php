<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\InvalidLocationException;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\General;

final class WikipediaService extends AbstractService
{
	const NAME = 'Wikipedia';

	const LINK = 'https://wikipedia.org';

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
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public static function isValid(string $url): bool
	{
		$parsedUrl = parse_url($url);
		$allowedHost = 'wikipedia.org';
		$allowedPath = '/wiki/';
		$allowedPathShort = '/w/';
		return (
			isset($parsedUrl['path'])
			&&
			( // path startwith
				mb_substr($parsedUrl['path'], 0, mb_strlen($allowedPath)) === $allowedPath
				||
				mb_substr($parsedUrl['path'], 0, mb_strlen($allowedPathShort)) === $allowedPathShort
			)
			&& // domain endwith @author https://stackoverflow.com/questions/834303/startswith-and-endswith-functions-in-php#comment14337453_834355
			mb_substr($parsedUrl['host'], -mb_strlen($allowedHost)) === $allowedHost
		);
	}

	/**
	 * @param string $url
	 * @return BetterLocation
	 * @throws InvalidLocationException|\JsonException
	 */
	public static function parseCoords(string $url): BetterLocation
	{
		$response = self::requestLocationFromWikipediaPage($url);
		if (isset($response->wgCoordinates) && isset($response->wgCoordinates->lat) && isset($response->wgCoordinates->lon)) {
			return new BetterLocation(
				$url,
				$response->wgCoordinates->lat,
				$response->wgCoordinates->lon,
				self::class
			);
		} else {
			// @TODO maybe should return null instead of error since not all pages has saved location
			throw new InvalidLocationException('No valid location found');
		}
	}

	/**
	 * @param string $url
	 * @return BetterLocationCollection
	 * @throws NotImplementedException
	 */
	public static function parseCoordsMultiple(string $url): BetterLocationCollection
	{
		throw new NotImplementedException('Parsing multiple coordinate is not supported. Use parseCoords() instead.');
	}

	/**
	 * @param $url
	 * @return \stdClass
	 * @throws \JsonException|\Exception
	 */
	private static function requestLocationFromWikipediaPage($url): \stdClass
	{
		$response = General::fileGetContents($url, [
			CURLOPT_CONNECTTIMEOUT => 5,
			CURLOPT_TIMEOUT => 5,
			// CURLOPT_RANGE => '0-500', // Not working for *.here URLs. Their server is probably forcing full request
		]);
		$startString = '<script>document.documentElement.className="client-js";RLCONF=';
		$endString = 'RLSTATE=';
		$posStart = mb_strpos($response, $startString);
		$posEnd = mb_strpos($response, $endString);
		$jsonText = mb_substr(
			$response,
			mb_strlen($startString) + $posStart,
			$posEnd - $posStart - mb_strlen($startString),
		);
		$jsonText = rtrim($jsonText, " \t\n\r\0\x0B;"); // default whitespace trim and ;
		$jsonText = preg_replace('/:\s?!0/', ':false', $jsonText); // replace !0 with false
		$jsonText = preg_replace('/:\s?!1/', ':true', $jsonText); // replace !1 with true
		return json_decode($jsonText, false, 512, JSON_THROW_ON_ERROR);
	}
}
