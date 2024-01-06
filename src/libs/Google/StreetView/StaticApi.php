<?php declare(strict_types=1);

namespace App\Google\StreetView;

use App\Config;
use App\Google\RunGoogleApiRequestTrait;

class StaticApi
{
	use RunGoogleApiRequestTrait;

	private const METADATA_URL = 'https://maps.googleapis.com/maps/api/streetview/metadata';

	/**
	 * @param ?string $location Can be either a text string (such as Chagrin Falls, OH) or a comma-separated pair of latitude/longitude coordinates (40.457375,-80.009353).
	 * @param ?string $pano a specific panorama ID. These are generally stable, though panoramas may change ID over time as imagery is refreshed.
	 */
	private function getMetadataUrl(?string $location, ?string $pano = null): string
	{
		$queryParams = [
			'key' => $this->apiKey,
		];
		if ($location !== null) {
			$queryParams['location'] = $location;
		}
		if ($pano !== null) {
			$queryParams['pano'] = $pano;
		}
		return self::METADATA_URL . '?' . http_build_query($queryParams);
	}

	public function loadPanoaramaMetadataByCoords(float $lat, float $lon): ?StreetViewResponse
	{
		$input = $lat . ',' . $lon;
		$url = $this->getMetadataUrl($input);
		$response = $this->runGoogleApiRequest($url);
		if ($response === null) {
			return null;
		}

		return StreetViewResponse::cast($response);
	}

	function cacheTtl(): int
	{
		return Config::CACHE_TTL_GOOGLE_STREETVIEW_API;
	}
}
