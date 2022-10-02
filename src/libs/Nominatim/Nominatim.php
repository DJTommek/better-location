<?php declare(strict_types=1);

namespace App\Nominatim;

use App\Factory;
use App\Utils\CoordinatesInterface;
use maxh\Nominatim\Exceptions\NominatimException;

class Nominatim
{
	const ERRORS_WHITELIST = [
		'Unable to geocode',
	];

	public static function reverse(CoordinatesInterface $coordinates): ?array
	{
		$cacheKey = sprintf('reverse-%F-%F', $coordinates->getLat(), $coordinates->getLon());
		return Factory::Cache('nominatim')->load($cacheKey, function () use ($coordinates) {
			return self::reverseReal($coordinates);
		});
	}

	private static function reverseReal(CoordinatesInterface $coordinates)
	{
		$nominatimApi = Factory::Nominatim();
		$query = $nominatimApi->newReverse()->latlon($coordinates->getLat(), $coordinates->getLon());
		$result = $nominatimApi->find($query);
		if (isset($result['error'])) {
			if (in_array($result['error'], self::ERRORS_WHITELIST, true)) {
				return null;
			} else {
				throw new NominatimException(sprintf('Nominatim API returned error: "%s"', $result['error']));
			}
		} else {
			return $result;
		}
	}
}
