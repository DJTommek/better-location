<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\GoogleMapsService;
use App\Config;
use App\MiniCurl\MiniCurl;
use Tracy\Debugger;
use Tracy\ILogger;

class GooglePlaceApi
{
	private $apiKey;

	const TEXT_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
	const PLACE_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
	const PLACE_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

	/**
	 * More responses on https://developers.google.com/places/web-service/search#PlaceSearchStatusCodes
	 */
	const RESPONSE_ZERO_RESULTS = 'ZERO_RESULTS';
	const RESPONSE_OK = 'OK';

	public function __construct()
	{
		$this->apiKey = Config::GOOGLE_PLACE_API_KEY;
	}

	/**
	 * @param string $input What should be searched
	 * @param string[] $outputFields @see https://developers.google.com/places/web-service/search#Fields
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param string|null $locationBias @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return string
	 */
	private function gePlaceSearchUrl(string $input, array $outputFields, string $language, ?string $locationBias = null): string
	{
		return self::PLACE_SEARCH_URL . '?' . http_build_query([
				'input' => $input,
				'inputtype' => 'textquery', // @TODO add support for phonenumber?
				'fields' => join(',', $outputFields),
				'locationbias' => $locationBias, // if null, value will not present in result string (https://www.php.net/manual/en/function.http-build-query.php#60523)
				'language' => $language,
				'key' => $this->apiKey,
			]);
	}

	/**
	 * @see https://developers.google.com/places/web-service/search#TextSearchRequests
	 * @param string $input What should be searched
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param string|null $location
	 * @return string
	 */
	private function geTextSearchUrl(string $input, string $language, ?string $location = null): string
	{
		$params = [
			'key' => $this->apiKey,
			'query' => $input,
		];
		if ($location) {
			$params['location'] = $location;
			$params['radius'] = 50000; // maximum is 50 000 meters
		}
		return self::TEXT_SEARCH_URL . '?' . http_build_query($params);
	}

	/**
	 * @param string $placeId
	 * @param string[] $outputFields see https://developers.google.com/places/web-service/details#fields
	 * @return string
	 */
	private function gePlaceDetailsUrl(string $placeId, array $outputFields): string
	{
		return self::PLACE_DETAILS_URL . '?' . http_build_query([
				'place_id' => $placeId,
				'fields' => join(',', $outputFields),
				'key' => $this->apiKey,
			]);
	}

	/**
	 * @param string $input What should be searched
	 * @param string[] $outputFields @see https://developers.google.com/places/web-service/search#Fields
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param BetterLocation|null $locationBias @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return \stdClass[]
	 * @throws \JsonException|\Exception
	 */
	public function runPlaceSearch(string $input, array $outputFields, string $language, ?BetterLocation $locationBias = null): array
	{
		$url = $this->gePlaceSearchUrl($input, $outputFields, $language, ($locationBias ? $this->generateLocationBias($locationBias) : null));
		$content = $this->runGoogleApiRequest($url);
		if ($content->status === self::RESPONSE_ZERO_RESULTS) {
			return [];
		}
		return $content->candidates;
	}

	/**
	 * @param string $input What should be searched
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param BetterLocation|null $location @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return \stdClass[]
	 * @throws \JsonException|\Exception
	 */
	public function runTextSearch(string $input, string $language, ?BetterLocation $location = null): array
	{
		$url = $this->geTextSearchUrl($input, $language, ($location ? $location->__toString() : null));
		$content = $this->runGoogleApiRequest($url);
		if ($content->status === self::RESPONSE_ZERO_RESULTS) {
			return [];
		}
		return $content->results;
	}

	/**
	 * @param string $placeId
	 * @param string[] $outputFields see https://developers.google.com/places/web-service/details#fields
	 * @return \stdClass
	 * @throws \JsonException|\Exception
	 */
	public function getPlaceDetails(string $placeId, array $outputFields): \stdClass
	{
		$url = $this->gePlaceDetailsUrl($placeId, $outputFields);
		$content = $this->runGoogleApiRequest($url);
		return $content->result;
	}

	private function runGoogleApiRequest(string $url): \stdClass
	{
		$response = (new MiniCurl($url))->allowCache(Config::CACHE_TTL_GOOGLE_PLACE_API)->run();
		$content = $response->getBodyAsJson();
		if (in_array($content->status, [self::RESPONSE_OK, self::RESPONSE_ZERO_RESULTS], true)) {
			return $content;
		} else {
			Debugger::log('Request URL: ' . $url, ILogger::DEBUG);
			Debugger::log('Response content: ' . $response->getBody(), ILogger::DEBUG);
			throw new \Exception(sprintf('Invalid status "%s" from Google Place API. Error: "%s". See debug.log for more info.', $content->status, $content->error_message ?? 'Not provided'));
		}
	}

	private function generateLocationBias(BetterLocation $betterLocation)
	{
		return 'point:' . $betterLocation->__toString();
	}

	/**
	 * Helper method to do all logic and return collection of locations
	 */
	public static function search(string $queryInput, ?string $languageCode = null, ?BetterLocation $location = null): BetterLocationCollection
	{
		$queryInput = self::normalizeInput($queryInput);
		$placeApi = new self();
		$placeCandidates = $placeApi->runPlaceSearch(
			$queryInput,
			['formatted_address', 'name', 'geometry', 'place_id'],
			$languageCode ?? 'en',
			$location,
		);
		$collection = new BetterLocationCollection();
		foreach ($placeCandidates as $placeCandidate) {
			$betterLocation = new BetterLocation(
				$queryInput,
				$placeCandidate->geometry->location->lat,
				$placeCandidate->geometry->location->lng,
				GoogleMapsService::class,
				GoogleMapsService::TYPE_INLINE_SEARCH,
			);
			$address = $placeCandidate->formatted_address;
			try {
				$placeDetails = $placeApi->getPlaceDetails($placeCandidate->place_id, ['url', 'website', 'international_phone_number']);
				$betterLocation->setPrefixMessage(sprintf('<a href="%s">%s</a>', ($placeDetails->website ?? $placeDetails->url), $placeCandidate->name));
				if (isset($placeDetails->international_phone_number)) {
					$address .= sprintf(' (%s)', $placeDetails->international_phone_number);
				}
			} catch (\Throwable $exception) {
				Debugger::log($exception, ILogger::EXCEPTION);
				$betterLocation->setPrefixMessage($placeCandidate->name);
			}
			$betterLocation->setAddress($address);
			$collection->add($betterLocation);
		}
		return $collection;
	}

	/**
	 * Replace newlines with spaces and remove whitespaces from beginning and end of string
	 */
	public static function normalizeInput(string $input): string
	{
		return preg_replace('/\s+/', ' ', $input);
	}
}
