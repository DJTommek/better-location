<?php declare(strict_types=1);

namespace App\Web\Api\v1;

use App\BetterLocation\FromTelegramMessage;
use App\BetterLocation\GooglePlaceApi;
use App\Config;
use App\Web\MainPresenter;
use Tracy\Debugger;

class InputProcessPresenter extends MainPresenter
{
	/**
	 * @param list<string> $apiKeys
	 */
	public function __construct(
		private readonly FromTelegramMessage $fromTelegramMessage,
		#[\SensitiveParameter] private readonly array $apiKeys,
		private readonly ?GooglePlaceApi $googlePlaceApi = null,
	) {
	}

	public function action(): never
	{
		Debugger::$showBar = false;

		$apiKey = $this->getParam('api_key');
		if ($apiKey === null) {
			$this->apiResponse(true, 'API key is missing.', httpCode: self::HTTP_UNAUTHORIZED);
		}
		if (!in_array($apiKey, $this->apiKeys, true)) {
			$this->apiResponse(true, 'API key is not valid.', httpCode: self::HTTP_UNAUTHORIZED);
		}

		$input = $this->getParam('input');
		if ($input === null) {
			$this->apiResponse(true, 'Input is missing.', httpCode: self::HTTP_BAD_REQUEST);
		}

		$fulltextSearchRaw = $this->getParam('fulltextsearch');
		$fulltextSearch = $fulltextSearchRaw !== null;

		try {
			$entities = \App\TelegramCustomWrapper\TelegramHelper::generateEntities($input);
			$locations = $this->fromTelegramMessage->getCollection($input, $entities);

			if (
				$fulltextSearch === true
				&& $this->googlePlaceApi !== null
				&& $locations->isEmpty()
				&& mb_strlen($input) >= Config::GOOGLE_SEARCH_MIN_LENGTH
			) {
				try {
					$googleCollection = $this->googlePlaceApi->searchPlace($input);
					$locations->add($googleCollection);
				} catch (\Exception $exception) {
					Debugger::log($exception, Debugger::EXCEPTION);
				}
			}

			if ($locations->isEmpty()) {
				$this->apiResponse(false, 'No location(s) was detected in input.');
			}

			$result = [];
			foreach ($locations->getLocations() as $location) {
				$result[] = $location->export();
			}
			$this->apiResponse(false, result: $result);
		} catch (\Exception $exception) {
			$this->apiResponse(
				true,
				sprintf('Error occured while processing input: %s', $exception->getMessage()),
				httpCode: self::HTTP_INTERNAL_SERVER_ERROR,
			);
		}
	}

	private function getParam(string $key): ?string
	{
		return $this->request->getQuery($key) ?? $this->request->getPost($key);
	}
}

