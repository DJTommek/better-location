<?php declare(strict_types=1);

namespace App\Web\Api\v1;

use App\BetterLocation\Service\AbstractService;
use App\BetterLocation\ServicesManager;
use App\BetterLocation\Url;
use App\Config;
use App\Utils\Requestor;
use App\Utils\Strict;
use DJTommek\Coordinates\CoordinatesInterface;
use Nette\Utils\Json;
use Tracy\Debugger;

class UrlProcessPresenter extends AbstractPresenter
{
	/** @param list<string> $apiKeys */
	public function __construct(
		private readonly ServicesManager $servicesManager,
		private readonly Requestor $requestor,
		#[\SensitiveParameter] array $apiKeys,
	) {
		parent::__construct($apiKeys);
	}

	#[\Override]
	public function action(): never
	{
		Debugger::$showBar = false;

		$this->assertApiKey();

		$body = $this->request->getRawBody() ?? '';
		try {
			$data = Json::decode($body, true);
		} catch (\Throwable) {
			$this->apiResponse(true, 'Request body is not valid JSON.', httpCode: self::HTTP_BAD_REQUEST);
		}
		$input = trim($data['input'] ?? '');

		if ($input === '') {
			$this->apiResponse(true, 'Input URL is missing.', httpCode: self::HTTP_BAD_REQUEST);
		}

		if (Strict::isUrl($input) === false) {
			$this->apiResponse(true, 'Input is not valid URL.', httpCode: self::HTTP_BAD_REQUEST);
		}

		$result = [
			'locations' => [],
		];

		try {
			$input = $this->handleShortUrl($input);

            // @TODO Make API key optional and if it is missing, allow only "offline" processing of services.
			$foundLocations = $this->servicesManager->iterate($input);
			if ($foundLocations->filterTooClose) {
				$foundLocations->filterTooClose(Config::DISTANCE_IGNORE);
			}

			foreach ($foundLocations as $location) {
				$resultServices = [];
                // @TODO Make API key optional and if it is missing, allow generating only "offline" services.
				foreach ($this->servicesManager->getServices() as $serviceClass) {
					try {
						$service = $this->servicesManager->getServiceInstance($serviceClass);
						$resultServices[] = $this->website($service, $location);
					} catch (\Throwable $exception) {
						Debugger::log($exception, Debugger::EXCEPTION);
					}
				}
				$result['locations'][] = [
					'lat' => $location->getLat(),
					'lon' => $location->getLon(),
					'address' => $location->getAddress(),
					'services' => array_values(array_filter($resultServices)),
				];
			}

			$this->apiResponse(
				false,
				sprintf('Found %d location(s)', count($result['locations'])),
				result: $result,
			);
		} catch (\Exception $exception) {
			$this->apiResponse(
				true,
				sprintf('Error occured while processing input: %s', $exception->getMessage()),
				httpCode: self::HTTP_INTERNAL_SERVER_ERROR,
			);
		}
	}

	private function handleShortUrl(string $url): string
	{
		if (!Url::isShortUrl($url)) {
			return $url;
		}
		return $this->requestor->loadFinalRedirectUrl($url);
	}

	/**
	 * @return array{share?: string, drive?: string, text?: string, 'static'?: string, name?: string}
	 */
	private function website(AbstractService $service, CoordinatesInterface $coordinates): array
	{
		$lat = $coordinates->getLat();
		$lon = $coordinates->getLon();
		$result = [];
		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_SHARE)
			&& $output = $service::getShareLink($lat, $lon)
		) {
			$result['share'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_DRIVE)
			&& $output = $service::getDriveLink($lat, $lon)
		) {
			$result['drive'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_TEXT)
			&& $output = $service::getShareText($lat, $lon)
		) {
			$result['text'] = $output;
		}

		if (
			$service::hasTag(ServicesManager::TAG_GENERATE_LINK_IMAGE)
			&& $output = $service->getScreenshotLink($coordinates)
		) {
			$result['static'] = $output;
		}

		if ($result !== []) {
			$result['name'] = $service::getName();
		}
		return $result;
	}

}

