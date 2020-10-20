<?php
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';
//\Tracy\Debugger::$showBar = false;

$response = new \stdClass();
$response->datetime = (new \DateTimeImmutable())->format(DateTimeInterface::W3C);
$response->result = [];
$response->error = true;
$response->message = null;

try {
	$crons = Cron::loadAll();
	foreach ($crons as $cron) {
		$cron->run();
	}
	$response->error = false;
	$response->result[] = 'aaa';
	$response->message = 'bbbb';
} catch (\Exception $exception) {
	$response->error = true;
	$response->message = sprintf('%s Error occured while processing Glympse CRON: %s', Icons::ERROR, $exception->getMessage());
	throw $exception;
}
//die(json_encode($response));
