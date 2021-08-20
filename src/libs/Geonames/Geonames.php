<?php declare(strict_types=1);

namespace App\Geonames;

use App\Factory;
use App\Geonames\Types\TimezoneType;
use Nette\Caching\Cache;

class Geonames
{
	public static function timezone(float $lat, float $lon): TimezoneType
	{
		$cacheKey = sprintf('timezone-%F-%F', $lat, $lon);
		return Factory::Cache('geonames')->load($cacheKey, function (&$dependencies) use ($lat, $lon) {
			$dependencies[Cache::EXPIRE] = '5 minutes';
			return self::timezoneReal($lat, $lon);
		});
	}

	private static function timezoneReal(float $lat, float $lon)
	{
		$client = Factory::Geonames();
		$result = $client->timezone([
			'lat' => $lat,
			'lng' => $lon,
		]);
		return TimezoneType::fromResponse($result);
	}

}
