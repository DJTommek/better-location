<?php
declare(strict_types=1);

use App\BetterLocation\BetterLocationCollection;
use App\Config;
use App\Factory;
use Tracy\Debugger;

require_once __DIR__ . '/../../src/bootstrap.php';
Debugger::$showBar = false;

$response = new \stdClass();
$response->datetime = (new \DateTimeImmutable())->format(DateTimeInterface::W3C);
$response->result = [];
$response->error = false;
$response->message = null;

$apiKey = $_POST['api_key'] ?? $_GET['api_key'] ?? null;
if (in_array($apiKey, \App\Config::API_KEYS, true)) {
	try {
		$input = $_POST['input'] ?? $_GET['input'] ?? null;

		$fulltextSearchRaw = $_POST['fulltextsearch'] ?? $_GET['fulltextsearch'] ?? null;
		$fulltextSearch = $fulltextSearchRaw !== null;

		if ($input !== null) {
			$entities = \App\TelegramCustomWrapper\TelegramHelper::generateEntities($input);
			$betterLocations = BetterLocationCollection::fromTelegramMessage($input, $entities);
			if (
				$fulltextSearch === true
				&& $betterLocations->isEmpty()
				&& mb_strlen($input) >= Config::GOOGLE_SEARCH_MIN_LENGTH
				&& Config::isGooglePlaceApi()
			) {
				try {
					$placeApi = Factory::googlePlaceApi();
					$googleCollection = $placeApi->searchPlace($input);
					$betterLocations->add($googleCollection);
				} catch (\Exception $exception) {
					Debugger::log($exception, Debugger::EXCEPTION);
				}
			}

			if ($betterLocations->isEmpty()) {
				$response->message = 'No location(s) was detected in text.';
			} else {
				foreach ($betterLocations->getLocations() as $betterLocation) {
					$response->result[] = $betterLocation->export();
				}
			}
		} else {
			$response->message = 'POST/GET "input" is missing.';
		}
	} catch (\Exception $exception) {
		$response->error = true;
		$response->message = sprintf('%s Error occured while processing input: %s', App\Icons::ERROR, $exception->getMessage());
	}
} else {
	$response->message = 'Invalid API key.';
}
die(json_encode($response));
