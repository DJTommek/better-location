<?php declare(strict_types=1);

namespace App\Nominatim;

use App\Config;
use App\Factory;
use DJTommek\Coordinates\CoordinatesInterface;
use maxh\Nominatim\Exceptions\NominatimException;

class Nominatim
{
	const ERRORS_WHITELIST = [
		'Unable to geocode',
	];

	public static function reverse(CoordinatesInterface $coordinates): ?ReverseResponseDto
	{
		$cacheKey = sprintf('reverse-%F-%F', $coordinates->getLat(), $coordinates->getLon());
		return Factory::cache(Config::CACHE_NAMESPACE_NOMINATIM)->load($cacheKey, function () use ($coordinates) {
			return self::reverseReal($coordinates);
		});
	}

	private static function reverseReal(CoordinatesInterface $coordinates): ?ReverseResponseDto
	{
		$nominatimApi = Factory::nominatim();
		$query = $nominatimApi->newReverse()->latlon($coordinates->getLat(), $coordinates->getLon());
		$result = $nominatimApi->find($query);
		if (isset($result['error'])) {
			if (in_array($result['error'], self::ERRORS_WHITELIST, true)) {
				return null;
			}
			throw new NominatimException(sprintf('Nominatim API returned error: "%s"', $result['error']));
		}

		return ReverseResponseDto::cast($result);
	}
}
