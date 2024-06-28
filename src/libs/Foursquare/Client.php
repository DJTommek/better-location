<?php declare(strict_types=1);

namespace App\Foursquare;

use App\Foursquare\Types\VenueType;
use App\Utils\Requestor;

class Client
{
	const LINK = 'https://foursquare.com';
	const LINK_API = 'https://api.foursquare.com';
	const LINK_API_VENUE_DETAIL = self::LINK_API . '/v2/venues/%s';

	public function __construct(
		private readonly Requestor $requestor,
		private readonly string $clientId,
		private readonly string $clientSecret,
		private readonly ?int $cacheTtl = null,
	) {
	}

	public function loadVenue(string $venueId): VenueType
	{
		$url = sprintf(self::LINK_API_VENUE_DETAIL, $venueId);
		$json = $this->makeJsonRequest($url, 20201126);
		if ($json->meta->code !== 200) {
			throw new \Exception(sprintf(
				'Loading venue responded with bad HTTP response code %d. Error type: "%s", Error detail: "%s"',
				$json->meta->code,
				$json->meta->errorType,
				$json->meta->errorDetail ?? 'Unspecified',
			));
		}

		return VenueType::createFromVariable($json->response->venue);
	}

	/** @param int $version API version in format YYYYMMDD */
	private function makeJsonRequest(string $url, int $version): \stdClass
	{
		$queryParams = [
			'client_id' => $this->clientId,
			'client_secret' => $this->clientSecret,
			'v' => $version,
		];
		$requestUrl = $url . '?' . http_build_query($queryParams);
		return $this->requestor->getJson($requestUrl, $this->cacheTtl);
	}
}
