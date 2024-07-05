<?php declare(strict_types=1);

namespace App\BetterLocation;

use App\BetterLocation\Service\GoogleMapsService;
use App\Config;
use App\Factory;
use App\Google\Geocoding\GeocodeResponse;
use App\Google\RunGoogleApiRequestTrait;
use App\Icons;
use App\Utils\Utils;
use DJTommek\Coordinates\CoordinatesInterface;
use Tracy\Debugger;
use Tracy\ILogger;

class GooglePlaceApi
{
	use RunGoogleApiRequestTrait;

	private const TEXT_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/textsearch/json';
	private const PLACE_SEARCH_URL = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';
	private const PLACE_DETAILS_URL = 'https://maps.googleapis.com/maps/api/place/details/json';

	// https://developers.google.com/maps/documentation/places/web-service/details#Place-business_status
	public const BUSINESS_STATUS_OPERATIONAL = 'OPERATIONAL';
	public const BUSINESS_STATUS_CLOSED_TEMPORARILY = 'CLOSED_TEMPORARILY';
	public const BUSINESS_STATUS_CLOSED_PERMANENTLY = 'CLOSED_PERMANENTLY';

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
	 * @param CoordinatesInterface|null $locationBias @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return \stdClass[]
	 * @throws \JsonException|\Exception
	 */
	public function runPlaceSearch(string $input, array $outputFields, string $language, ?CoordinatesInterface $locationBias = null): array
	{
		$url = $this->gePlaceSearchUrl($input, $outputFields, $language, ($locationBias === null ? null : $this->generateLocationBias($locationBias)));
		$content = $this->runGoogleApiRequest($url);
		if ($content === null) {
			return [];
		}

		return $content->candidates;
	}

	/**
	 * @param string $input What should be searched
	 * @param string $language @see https://developers.google.com/maps/faq#languagesupport
	 * @param CoordinatesInterface|null $location @see https://developers.google.com/places/web-service/search#FindPlaceRequests -> Optional parameters
	 * @return \stdClass[]
	 * @throws \JsonException|\Exception
	 */
	public function runTextSearch(string $input, string $language, ?CoordinatesInterface $location = null): array
	{
		$url = $this->geTextSearchUrl($input, $language, $location?->getLatLon());

		$content = $this->runGoogleApiRequest($url);
		if ($content === null) {
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
	public function getPlaceDetails(string $placeId, array $outputFields): ?\stdClass
	{
		$url = $this->gePlaceDetailsUrl($placeId, $outputFields);
		$response = $this->runGoogleApiRequest($url);
		return $response?->result;
	}

	private function generateLocationBias(CoordinatesInterface $location): string
	{
		return 'point:' . $location->getLatLon();
	}

	/**
	 * Helper method to do all logic and return collection of locations
	 */
	public function searchPlace(string $queryInput, ?string $languageCode = null, ?CoordinatesInterface $location = null, bool $loadPlaceDetails = true): BetterLocationCollection
	{
		$queryInput = self::normalizeInput($queryInput);
		$placeCandidates = $this->runPlaceSearch(
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

			$address = $placeCandidate->formatted_address ?? null;

			if ($loadPlaceDetails) {
				try {
					$placeDetails = $this->getPlaceDetails($placeCandidate->place_id, ['name', 'formatted_address', 'url', 'website', 'international_phone_number', 'business_status', 'address_components']);
					if ($placeDetails === null) {
						if ($placeCandidate->name !== '') {
							$betterLocation->setPrefixMessage($placeCandidate->name);
						}
					} else {
						self::populateLocationFromPlaceDetails($betterLocation, $placeDetails);
					}
				} catch (\Throwable $exception) {
					Debugger::log($exception, ILogger::EXCEPTION);
				}
			}

			if ($address !== null) {
				$betterLocation->setAddress($address);
			}
			$collection->add($betterLocation);
		}
		return $collection;
	}

	/**
	 * Load data from placeDetails and insert it into provided BetterLocation
	 */
	public static function populateLocationFromPlaceDetails(BetterLocation $location, \stdClass $placeDetails): void
	{
		$location->setPrefixMessage(
			sprintf(
				'<a href="%s">%s</a>',
				($placeDetails->website ?? $placeDetails->url),
				$placeDetails->name,
			),
		);

		if (isset($placeDetails->business_status)) {
			if ($placeDetails->business_status === self::BUSINESS_STATUS_CLOSED_TEMPORARILY) {
				$location->addDescription(Icons::WARNING . ' Temporarily closed');
			} else if ($placeDetails->business_status === self::BUSINESS_STATUS_CLOSED_PERMANENTLY) {
				$location->addDescription(Icons::WARNING . ' Permanently closed');
			}
		}

		assert(isset($placeDetails->formatted_address));
		$address = $placeDetails->formatted_address;

		if (isset($placeDetails->international_phone_number)) {
			$address .= sprintf(' (%s)', $placeDetails->international_phone_number);
		}

		$countryCode = self::getCountryCodeFromAddressComponents($placeDetails->address_components);
		if ($countryCode !== null) {
			$address = Utils::flagEmojiFromCountryCode($countryCode) . ' ' . $address;
		}
		$location->setAddress($address);

	}

	/**
	 * Replace newlines with spaces and remove whitespaces from beginning and end of string
	 */
	public static function normalizeInput(string $input): string
	{
		return preg_replace('/\s+/', ' ', $input);
	}

	/**
	 * @param array<object{long_name: string, short_name: string, types: array<string>}> $addressComponents
	 * @internal For tests
	 */
	public static function getCountryCodeFromAddressComponents(array $addressComponents): ?string
	{
		foreach ($addressComponents as $addressComponent) {
			if (in_array(GeocodeResponse::ADDRESS_COMPONENT_COUNTRY, $addressComponent->types, true)) {
				return $addressComponent->short_name;
			}
		}
		return null;
	}

	private function cacheTtl(): int
	{
		return Config::CACHE_TTL_GOOGLE_PLACE_API;
	}
}
