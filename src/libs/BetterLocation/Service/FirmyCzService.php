<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use App\BetterLocation\Service\Exceptions\NotImplementedException;
use App\BetterLocation\Service\Exceptions\NotSupportedException;
use App\Utils\General;
use DJTommek\MapyCzApi\MapyCzApi;

final class FirmyCzService extends AbstractService
{
	const NAME = 'Firmy.cz';

	const LINK = 'https://firmy.cz';

	const URL_PATH_REGEX = '/^\/detail\/([0-9]+)/';

	/** @throws NotSupportedException */
	public static function getLink(float $lat, float $lon, bool $drive = false): string
	{
		if ($drive) {
			throw new NotSupportedException('Drive link is not supported.');
		} else {
			throw new NotSupportedException('Share link is not supported.');
		}
	}

	public static function isValid(string $input): bool
	{
		return self::isUrl($input);
	}

	public static function parseCoords(string $input): BetterLocation
	{
		throw new NotImplementedException('Parsing coordinates is not available.');
	}

	public static function isUrl(string $url): bool
	{
		$url = mb_strtolower($url);
		$parsedUrl = General::parseUrl($url);
		return (
			isset($parsedUrl['host']) &&
			in_array($parsedUrl['host'], ['firmy.cz', 'www.firmy.cz']) &&
			isset($parsedUrl['path']) &&
			preg_match(self::URL_PATH_REGEX, $parsedUrl['path'])
		);
	}

	public static function parseUrl(string $url): BetterLocation
	{
		$parsedUrl = General::parseUrl($url);
		preg_match(self::URL_PATH_REGEX, $parsedUrl['path'], $matches);
		$firmId = (int)$matches[1];
		$mapyCzApi = new MapyCzApi();
		$firmDetail = $mapyCzApi->loadPoiDetails('firm', $firmId);
		$location = new BetterLocation($url, $firmDetail->getLat(), $firmDetail->getLon(), self::class);
		$location->setPrefixMessage(sprintf('<a href="%s">%s %s</a>', $url, self::NAME, $firmDetail->title));
		$location->setAddress($firmDetail->titleVars->locationMain1);
		return $location;
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
