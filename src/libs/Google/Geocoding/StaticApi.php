<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Config;
use App\Google\ResponseCodes;
use App\Google\RunGoogleApiRequestTrait;
use App\MiniCurl\MiniCurl;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class StaticApi
{
	use RunGoogleApiRequestTrait;

	private const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

	public function reverse(CoordinatesInterface $coordinates): ?GeocodeResponse
	{
		$queryParams = [
			'key' => $this->apiKey,
			'latlng' => $coordinates->key(),
		];
		$url = self::API_URL . '?' . http_build_query($queryParams);
		$response = $this->runGoogleApiRequest($url);
		if ($response === null) {
			return null;
		}
		return GeocodeResponse::cast($response);
	}

	private function runGoogleApiRequest(string $url): ?\stdClass
	{
		$response = (new MiniCurl($url))
			->allowAutoConvertEncoding(false)
			->allowCache(Config::CACHE_TTL_GOOGLE_GEOCODE_API)
			->run();
		$content = $response->getBodyAsJson();
		$status = ResponseCodes::customFrom($content->status);
		if ($status->isEmpty()) {
			return null;
		}

		if ($status->isError()) {
			if ($status === ResponseCodes::INVALID_REQUESTS) {
				// 2023-01-06: Ignore this error because it occures even for valid inputs such as:
				// - '25 11'N 064 39'E'
				// - 25 11N 064 39E
				// but apparently this input is valid:
				// - 2511 N 064 39E
				return null;
			}

			Debugger::log('Request URL: ' . $url, ILogger::DEBUG);
			Debugger::log('Response content: ' . $response->getBody(), ILogger::DEBUG);
			throw new \Exception(sprintf('Invalid status "%s" from Google Geocode Static API. Error: "%s". See debug.log for more info.', $content->status, $content->error_message ?? 'Not provided'));
		}

		return $content;
	}

	function cacheTtl(): int
	{
		return Config::CACHE_TTL_GOOGLE_GEOCODE_API;
	}
}
