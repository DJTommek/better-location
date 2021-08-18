<?php declare(strict_types=1);

namespace App\Nominatim;

use App\Factory;
use maxh\Nominatim\Exceptions\NominatimException;

class Nominatim
{
	const ERRORS_WHITELIST = [
		'Unable to geocode',
	];

	public static function reverse(float $lat, float $lon): ?array
	{
		$cacheKey = sprintf('reverse-%F-%F', $lat, $lon);
		return Factory::Cache('nominatim')->load($cacheKey, function () use ($lat, $lon) {
			return self::reverseReal($lat, $lon);
		});
	}

	private static function reverseReal(float $lat, float $lon)
	{
		$nominatimApi = Factory::Nominatim();
		$query = $nominatimApi->newReverse()->latlon($lat, $lon);
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
