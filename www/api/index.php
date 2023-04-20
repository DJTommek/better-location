<?php
declare(strict_types=1);

use App\BetterLocation\BetterLocation;
use App\BetterLocation\BetterLocationCollection;
use Tracy\Debugger;

require_once __DIR__ . '/../../src/bootstrap.php';
Debugger::$showBar = false;

$response = new \stdClass();
$response->datetime = (new \DateTimeImmutable())->format(DateTimeInterface::W3C);
$response->result = [];
$response->error = true;
$response->message = null;

if (isset($_GET['api_key']) && in_array($_GET['api_key'], \App\Config::API_KEYS, true)) {
	try {
		if (isset($_POST['input'])) {
			$input = $_POST['input'];
			$entities = \App\TelegramCustomWrapper\TelegramHelper::generateEntities($input);
			$betterLocations = BetterLocationCollection::fromTelegramMessage($input, $entities);
			if (count($betterLocations)) {
				$response->error = false;
				foreach ($betterLocations->getLocations() as $betterLocation) {
					if ($betterLocation instanceof BetterLocation) {
						$response->result[] = $betterLocation->export();
					} else if ($betterLocation instanceof \App\BetterLocation\Service\Exceptions\InvalidLocationException) {
						$response->message = htmlentities($betterLocation->getMessage());
					} else {
						Debugger::log($betterLocation, \Tracy\ILogger::EXCEPTION);
						$response->result = [];
						$response->message = 'Unhandled exception, try again later.';
						$response->error = true;
					}
				}
			} else {
				$response->message = 'No location(s) was detected in text.';
			}
		} else {
			$response->message = 'POST "input" is missing.';
		}
	} catch (\Exception $exception) {
		$response->error = true;
		$response->message = sprintf('%s Error occured while processing input: %s', App\Icons::ERROR, $exception->getMessage());
	}
} else {
	$response->message = 'Invalid API key.';
}
die(json_encode($response));
