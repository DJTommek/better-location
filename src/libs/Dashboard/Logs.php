<?php declare(strict_types=1);

namespace App\Dashboard;

use App\Config;
use App\Utils\DummyLogger;
use App\Utils\General;
use Tracy\ILogger;

class Logs
{
	public static function getLogs(\DateTimeImmutable $date, int $maxLines)
	{
		$logContents = [];
		foreach (DummyLogger::getLogNames() as $logName) {
			$logContents[$logName] = DummyLogger::getLogContent($logName, $date, $maxLines);
		}
		return $logContents;
	}

	public static function getTracyLogs(int $maxLines)
	{
		$logsContent = [];
		foreach (General::getClassConstants(ILogger::class) as $logName) {
			$logContent = self::getTracyLogContent($logName, $maxLines);
			if (count($logContent) > 0) {
				$logsContent[$logName] = $logContent;
			}
		}
		return $logsContent;
	}

	private static function getTracyLogContent(string $logName, int $maxLines)
	{
		$tracyLogPath = Config::FOLDER_DATA . '/tracy-log/' . $logName . '.log';
		$fileContent = General::tail($tracyLogPath, $maxLines);
		if ($fileContent === false) {
			return [];
		}
		return explode(PHP_EOL, $fileContent);
	}
}
