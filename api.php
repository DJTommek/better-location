<?php
declare(strict_types=1);

use BetterLocation\BetterLocation;
use Tracy\Debugger;

require_once __DIR__ . '/src/bootstrap.php';
Debugger::$showBar = false;

$response = new \stdClass();
$response->datetime = (new \DateTimeImmutable())->format(DateTimeInterface::W3C);
$response->result = [];
$response->error = true;
$response->message = null;

try {
	if (isset($_POST['input'])) {
		$input = $_POST['input'];
		$urls = \Utils\General::getUrls($_POST['input']);
		// Simulate Telegram message by creating URL entities
		$entities = [];
		foreach ($urls as $url) {
			$entity = new stdClass();
			$entity->type = 'url';
			$entity->offset = mb_strpos($input, $url);
			$entity->length = mb_strlen($url);
			$entities[] = $entity;
		}
		$betterLocations = BetterLocation::generateFromTelegramMessage($input, $entities);
		if (count($betterLocations)) {
			$response->error = false;
			foreach ($betterLocations->getAll() as $betterLocation) {
				if ($betterLocation instanceof BetterLocation) {
					$response->result[] = $betterLocation->export();
				} else if ($betterLocation instanceof \BetterLocation\Service\Exceptions\InvalidLocationException) {
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
	$response->message = sprintf('%s Error occured while processing input: %s', Icons::ERROR, $exception->getMessage());
}
die(json_encode($response));
