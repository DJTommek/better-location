<?php declare(strict_types=1);

namespace App\Utils;

class DummyLogger
{
	const NAME_ALL_REQUESTS = 'request';
	const NAME_FEEDBACK = 'feedback';
	const NAME_TELEGRAM_INPUT = 'telegram_input';
	const NAME_TELEGRAM_OUTPUT = 'telegram_output';
	const NAME_TELEGRAM_OUTPUT_RESPONSE = 'telegram_output_response';

	const FILE_EXTENSION = 'jsonl';
	const LINE_SEPARATOR = "\n"; // not PHP_EOL because it is \r\n on Windows

	public static function log(string $name, $content): void
	{
		if (!preg_match('/^[a-zA-Z0-9_]{1,30}$/', $name)) {
			throw new \InvalidArgumentException('Invalid log name.');
		}
		$name = mb_strtolower($name);
		$path = sprintf('%s/log/%s', \App\Config::FOLDER_DATA, $name);
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
			sprintf('%s/%s_%s.%s', $path, $name, $now->format(\App\Config::DATE_FORMAT), self::FILE_EXTENSION),
			json_encode($writeLogObject) . self::LINE_SEPARATOR,
			FILE_APPEND,
		);
	}
}
