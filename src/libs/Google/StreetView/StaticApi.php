<?php declare(strict_types=1);

namespace App\Google\StreetView;

use App\Config;
use App\MiniCurl\MiniCurl;
use Tracy\Debugger;
use Tracy\ILogger;

class StaticApi
{
	private string $apiKey;

	private const METADATA_URL = 'https://maps.googleapis.com/maps/api/streetview/metadata';

	// More responses on https://developers.google.com/maps/documentation/streetview/metadata#status-codes
	private const RESPONSE_ZERO_RESULTS = 'ZERO_RESULTS';
	private const RESPONSE_NOT_FOUND = 'NOT_FOUND';
	private const RESPONSE_OK = 'OK';

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

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
		if (in_array($response->status, [self::RESPONSE_ZERO_RESULTS, self::RESPONSE_NOT_FOUND], true)) {
			return null;
		}

		return StreetViewResponse::cast($response);
	}

	private function runGoogleApiRequest(string $url): \stdClass
	{
		$response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_GOOGLE_STREETVIEW_API)->run();
		$content = $response->getBodyAsJson();
		if (in_array($content->status, [self::RESPONSE_OK, self::RESPONSE_ZERO_RESULTS, self::RESPONSE_NOT_FOUND], true)) {
			return $content;
		} else {
			Debugger::log('Request URL: ' . $url, ILogger::DEBUG);
			Debugger::log('Response content: ' . $response->getBody(), ILogger::DEBUG);
			throw new \Exception(sprintf('Invalid status "%s" from Google Street View Static API. Error: "%s". See debug.log for more info.', $content->status, $content->error_message ?? 'Not provided'));
		}
	}
}
