<?php declare(strict_types=1);

namespace App\Google\Geocoding;

use App\Config;
use App\MiniCurl\MiniCurl;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class StaticApi
{
	private string $apiKey;

	private const API_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

	// More responses on https://developers.google.com/maps/documentation/streetview/metadata#status-codes
	private const RESPONSE_ZERO_RESULTS = 'ZERO_RESULTS';
	private const RESPONSE_NOT_FOUND = 'NOT_FOUND';
	private const RESPONSE_OK = 'OK';

	public function __construct(string $apiKey)
	{
		$this->apiKey = $apiKey;
	}

	public function reverse(CoordinatesInterface $coordinates): ?\stdClass
	{
		$queryParams = [
			'key' => $this->apiKey,
			'latlng' => $coordinates->key(),
		];
		$url = self::API_URL . '?' . http_build_query($queryParams);
		return $this->runGoogleApiRequest($url);
	}

	private function runGoogleApiRequest(string $url): ?\stdClass
	{
		$response = (new MiniCurl($url))
			->allowAutoConvertEncoding(false)
			->allowCache(Config::CACHE_TTL_GOOGLE_GEOCODE_API)
			->run();
		$content = $response->getBodyAsJson();

		if (in_array($content->status, [self::RESPONSE_ZERO_RESULTS, self::RESPONSE_NOT_FOUND], true)) {
			return null;
		}

		if ($content->status === self::RESPONSE_OK) {
			return $content;
		}

		Debugger::log('Request URL: ' . $url, ILogger::DEBUG);
		Debugger::log('Response content: ' . $response->getBody(), ILogger::DEBUG);
		throw new \Exception(sprintf('Invalid status "%s" from Google Geocode Static API. Error: "%s". See debug.log for more info.', $content->status, $content->error_message ?? 'Not provided'));
	}
}
