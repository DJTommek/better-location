<?php

namespace BetterLocation;

use Utils\General;

class GooglePlaceApi
{
	private $apiKey;

	const PLACE_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
	const PLACE_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

	/**
	 * More responses on https://developers.google.com/places/web-service/search#PlaceSearchStatusCodes
	 */
	const RESPONSE_ZERO_RESULTS = 'ZERO_RESULTS';
	const RESPONSE_OK = 'OK';

	public function __construct() {
		$this->apiKey = GOOGLE_PLACE_API_KEY;
	}

	/**
	 * @param string $input
	 * @param string[] $outputFields @see https://developers.google.com/places/web-service/search#Fields
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param string|null $locationBias @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return string
	 */
	private function gePlaceSearchUrl(string $input, array $outputFields, string $language, ?string $locationBias = null): string {
		return self::PLACE_SEARCH_URL . '?' . http_build_query([
				'input' => $input,
				'inputtype' => 'textquery', // @TODO add support for phonenumber?
				'fields' => join(',', $outputFields),
				'locationbias' => $locationBias,
				'language' => $language,
				'key' => $this->apiKey,
			]);
	}

	/**
	 * @param string $placeId
	 * @param string[] $outputFields see https://developers.google.com/places/web-service/details#fields
	 * @return string
	 */
	private function gePlaceDetailsUrl(string $placeId, array $outputFields): string {
		return self::PLACE_DETAILS_URL . '?' . http_build_query([
				'place_id' => $placeId,
				'fields' => join(',', $outputFields),
				'key' => $this->apiKey,
			]);
	}

	/**
	 * @param string $input
	 * @param string[] $outputFields @see https://developers.google.com/places/web-service/search#Fields
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param BetterLocation|null $locationBias @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return \stdClass[]
	 * @throws \JsonException|\Exception
	 */
	public function runSearch(string $input, array $outputFields, string $language, ?BetterLocation $locationBias = null): array {
		$url = $this->gePlaceSearchUrl($input, $outputFields, $language, ($locationBias ? $this->generateLocationBias($locationBias) : null));
		$response = General::fileGetContents($url);
		$content = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
		if ($content->status === self::RESPONSE_ZERO_RESULTS) {
			return [];
		}
		if ($content->status !== self::RESPONSE_OK) {
			throw new \Exception(sprintf('Invalid status (%s) from Google Place Search API. Error: "%s"', $content->status, $content->error_message ?? 'Not provided'));
		}
		return $content->candidates;
	}

	/**
	 * @param string $placeId
	 * @param string[] $outputFields see https://developers.google.com/places/web-service/details#fields
	 * @return \stdClass
	 * @throws \JsonException|\Exception
	 */
	public function getPlaceDetails(string $placeId, array $outputFields): \stdClass {
		$url = $this->gePlaceDetailsUrl($placeId, $outputFields);
		$response = General::fileGetContents($url);
		$content = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
		if ($content->status !== self::RESPONSE_OK) {
			throw new \Exception(sprintf('Invalid status (%s) from Google Place Details API. Error: "%s"', $content->status, $content->error_message ?? 'Not provided'));
		}
		return $content->result;
	}

	private function generateLocationBias(BetterLocation $betterLocation) {
		return sprintf('point:%f,%f', $betterLocation->getLat(), $betterLocation->getLon());
	}
}
