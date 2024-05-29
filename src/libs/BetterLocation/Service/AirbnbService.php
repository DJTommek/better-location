<?php declare(strict_types=1);

namespace App\BetterLocation\Service;

use App\BetterLocation\BetterLocation;
use App\Config;
use App\Utils\Requestor;
use DJTommek\Coordinates\Coordinates;
use Nette\Http\Url;
use Nette\Utils\Json;

final class AirbnbService extends AbstractService
{
	const ID = 51;
	const NAME = 'Airbnb';

	const LINK = 'https://www.airbnb.cz/';

	public function __construct(
		private readonly Requestor $requestor,
	) {
	}

	public function validate(): bool
	{
		if ($this->url === null) {
			return false;
		}

		$domain2 = $this->url->getDomain(2);
		$domain3 = $this->url->getDomain(3);
		if (
			!str_starts_with($domain2, 'airbnb.') // airbnb.com, airbnb.cz
			&& !str_starts_with($domain3, 'airbnb.com') // airbnb.com.ar, airbnb.com.hk
			&& !str_starts_with($domain3, 'airbnb.co') // airbnb.co.kr, airbnb.co.id
		) {
			return false;
		}

		$path = $this->url->getPath();
		if (preg_match('/^\/rooms\/([0-9]+)(?:\/|$)/', $path, $matches)) {
			$this->data->roomId = (int)$matches[1];
			return true;
		}

		return false;
	}

	public function process(): void
	{
		$roomId = $this->data->roomId;
		$apiResponse = $this->requestLocationFromAirbnbApi($roomId);

		$metadataCoords = $apiResponse->data->presentation->stayProductDetailPage->sections->metadata->loggingContext->eventDataLogging;
		$coords = new Coordinates($metadataCoords->listingLat, $metadataCoords->listingLng);

		$location = new BetterLocation($this->inputUrl, $coords->lat, $coords->lon, self::class);
		$roomTitle = $apiResponse->data->presentation->stayProductDetailPage->sections->metadata->seoFeatures->ogTags->ogDescription;
		$sharingTitle = $apiResponse->data->presentation->stayProductDetailPage->sections->metadata->sharingConfig->title;

		$location->appendToPrefixMessage(sprintf(
			' <a href="%s" target="_blank">%s</a>',
			'https://airbnb.com/rooms/' . $roomId,
			htmlspecialchars($roomTitle),
		));
		$location->addDescription(htmlspecialchars($sharingTitle));
		$this->collection->add($location);
	}

	private function requestLocationFromAirbnbApi(int $roomId): \stdClass
	{
		$httpHeaders = [
			'x-airbnb-api-key' => 'd306zoyjsyarp7ifhu67rjxn52tv0t20',
			'Accept' => 'application/json',
		];

		$apiUrl = $this->generateApiUrl($roomId);
		return $this->requestor->getJson(
			url: $apiUrl,
			headers: $httpHeaders,
			cacheTtl: Config::CACHE_TTL_AIRBNB,
		);
	}

	/*
	 * @example https://www.airbnb.com/api/v3/StaysPdpSections/db49ad8bc9dfc274212274bec0c9b70d03bf8d1cb79458127d2d08113f7ab0ff?variables=%7B%22id%22%3A%22U3RheUxpc3Rpbmc6MTY5NTg5MTg%3D%22%2C%22pdpSectionsRequest%22%3A%7B%22layouts%22%3A%5B%5D%7D%7D&extensions=%7B%22persistedQuery%22%3A%7B%22version%22%3A1%2C%22sha256Hash%22%3A%22db49ad8bc9dfc274212274bec0c9b70d03bf8d1cb79458127d2d08113f7ab0ff%22%7D%7D
	 */
	private function generateApiUrl(int $roomId): Url
	{
		$hash = 'db49ad8bc9dfc274212274bec0c9b70d03bf8d1cb79458127d2d08113f7ab0ff'; // Some magic hash
		// Requesting airbnb.com will data return in English by default
		$url = new Url('https://www.airbnb.com/api/v3/StaysPdpSections/' . $hash);
		$url->setQueryParameter('variables', $this->generateRequestVariables($roomId));
		$url->setQueryParameter('extensions', $this->generateRequestExtensions($hash));
		return $url;
	}

	private function generateRequestVariables(int $roomId): string
	{
		return Json::encode([
			'id' => base64_encode('StayListing:' . $roomId),
			'pdpSectionsRequest' => ['layouts' => []],
		]);
	}

	private function generateRequestExtensions(string $hash): string
	{
		return Json::encode([
			'persistedQuery' => [
				'version' => 1,
				'sha256Hash' => $hash,
			],
		]);
	}
}
