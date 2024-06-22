<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Config;
use App\Google\RunGoogleApiRequestTrait;
use DJTommek\Coordinates\CoordinatesInterface;

class StaticApi
{
	use RunGoogleApiRequestTrait;

	private const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

	public function reverse(CoordinatesInterface $coordinates): ?GeocodeResponse
	{
		$queryParams = [
			'key' => $this->apiKey,
			'latlng' => $coordinates->getLatLon(),
		];
		$url = self::API_URL . '?' . http_build_query($queryParams);
		$response = $this->runGoogleApiRequest($url);
		if ($response === null) {
			return null;
		}
		return GeocodeResponse::cast($response);
	}

	function cacheTtl(): int
	{
		return Config::CACHE_TTL_GOOGLE_GEOCODE_API;
	}
}
