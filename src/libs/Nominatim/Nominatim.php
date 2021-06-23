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
