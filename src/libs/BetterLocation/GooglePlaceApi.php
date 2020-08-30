<?php


namespace BetterLocation;


use BetterLocation\Service\GoogleMapsService;
use Tracy\Debugger;
use Tracy\ILogger;
use Utils\General;

class GooglePlaceApi
{
	private $apiKey;
	private $outputFields;

	const BASE_URL = 'https://maps.googleapis.com/maps/api/place/findplacefromtext/json';

	/**
	 * More responses on https://developers.google.com/places/web-service/search#PlaceSearchStatusCodes
	 */
	const RESPONSE_ZERO_RESULTS = 'ZERO_RESULTS';
	const RESPONSE_OK = 'OK';

	public function __construct(array $outputFields = ['formatted_address', 'name', 'geometry']) {
		$this->apiKey = GOOGLE_PLACE_API_KEY;
		$this->outputFields = $outputFields;
	}

	private function getUrl(string $input) {
		$params = [
			'input' => $input,
			'inputtype' => 'textquery', // @TODO add support for phonenumber?
			'fields' => join(',', $this->outputFields),
			'key' => $this->apiKey,
		];
		return self::BASE_URL . '?' . http_build_query($params);
	}

	/**
	 * @param string $input
	 * @return BetterLocationCollection
	 * @throws Service\Exceptions\InvalidLocationException|\Exception
	 */
	public function runSearch(string $input): BetterLocationCollection {
		$collection = new BetterLocationCollection();
		$url = $this->getUrl($input);
		$response = General::fileGetContents($url);
		$content = json_decode($response, false, 512, JSON_THROW_ON_ERROR);
		if ($content->status === self::RESPONSE_ZERO_RESULTS) {
			return $collection;
		}
		if ($content->status !== self::RESPONSE_OK) {
			Debugger::log($response, ILogger::DEBUG);
			throw new \Exception(sprintf('Invalid status (%s) from Google Place API. Error: "%s"', $content->status, $content->error_message ?? 'Not provided'));
		}
		foreach ($content->candidates as $candidate) {
			$betterLocation = new BetterLocation(
				$input,
				$candidate->geometry->location->lat,
				$candidate->geometry->location->lng,
				GoogleMapsService::class,
				GoogleMapsService::TYPE_INLINE_SEARCH,
			);
			$betterLocation->setPrefixMessage($candidate->name);
			$betterLocation->setAddress($candidate->formatted_address);
			$collection[] = $betterLocation;
		}
		return $collection;
	}
}