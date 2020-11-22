<?php declare(strict_types=1);

namespace App\Utils;

class SimpleLogger
{
	const NAME_ALL_REQUESTS = 'request';
	const NAME_FEEDBACK = 'feedback';
	const NAME_TELEGRAM_INPUT = 'telegram_input';
	const NAME_TELEGRAM_OUTPUT = 'telegram_output';
	const NAME_TELEGRAM_OUTPUT_RESPONSE = 'telegram_output_response';
	const NAME_CRON_AUTOREFRESH = 'cron_autorefresh';

	const FILE_EXTENSION = 'jsonl';
	const LINE_SEPARATOR = "\n"; // not PHP_EOL because it is \r\n on Windows

	public static function log(string $name, $content): void
	{
		if (self::isLogNameValid($name) === false) {
			throw new \InvalidArgumentException('Invalid log name.');
		}
		$filePath = self::getFilePath($name);
		$path = dirname($filePath);
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
		file_put_contents($filePath, json_encode($writeLogObject) . self::LINE_SEPARATOR, FILE_APPEND);
	}

	private static function getFilePath(string $logName, \DateTimeInterface $date = null): string
	{
		if (is_null($date)) {
			$date = new \DateTimeImmutable();
		}
		return sprintf('%s/log/%s/%s_%s.%s',
			\App\Config::FOLDER_DATA,
			$logName,
			$logName,
			$date->format(\App\Config::DATE_FORMAT),
			self::FILE_EXTENSION
		);
	}

	public static function getLogNames()
	{
		return General::getClassConstants(self::class, 'NAME_');
	}

	private static function isLogNameValid(string $name)
	{
		$constants = General::getClassConstants(self::class, 'NAME_');
		return in_array($name, $constants, true);
	}

	public static function getLogContent(string $name, \DateTimeInterface $date, int $numberOfLines)
	{
		if (self::isLogNameValid($name) === false) {
			throw new \InvalidArgumentException('Invalid log name.');
		}
		$fileName = self::getFilePath($name, $date);
		if (file_exists($fileName)) {
			$fileContent = General::tail($fileName, $numberOfLines);
			$lines = explode(self::LINE_SEPARATOR, $fileContent);
			return array_map(function ($line) {
				return json_decode($line, false, 512, JSON_THROW_ON_ERROR);
			}, $lines);
		} else {
			return [];
		}
	}

}
