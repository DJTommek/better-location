<?php

namespace Utils;

/**
 * Log manager.
 */
class DummyLogger
{

	const NAME_FEEDBACK = 'feedback';
	const NAME_TELEGRAM_INPUT = 'telegram_input';
	const NAME_TELEGRAM_OUTPUT = 'telegram_output';
	const NAME_TELEGRAM_OUTPUT_RESPONSE = 'telegram_output_response';

	public static function log(string $name, $content): void {
		if (!preg_match('/^[a-zA-Z0-9_]{1,30}$/', $name)) {
			throw new \InvalidArgumentException('Invalid log name.');
		}
		$name = mb_strtolower($name);
		$path = sprintf('%s/log/%s', FOLDER_DATA, $name);
		dump($path);
		if (!file_exists($path)) {
			mkdir($path, 0750, true);
		}

		$writeLogObject = new \stdClass();
		$now = new \DateTimeImmutable();
		$writeLogObject->datetime = $now->format(DATE_ISO8601);
		if (defined('LOG_ID')) {
			$writeLogObject->log_id = LOG_ID;
		}
		$writeLogObject->name = $name;
		$writeLogObject->content = $content;
		file_put_contents(
			sprintf('%s/%s_%s.log', $path, $name, $now->format(DATE_FORMAT)),
			json_encode($writeLogObject) . "\n",
			FILE_APPEND,
		);
	}
}
