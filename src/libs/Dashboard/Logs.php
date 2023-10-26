<?php declare(strict_types=1);

namespace App\Dashboard;

use App\Config;
use App\Utils\SimpleLogger;
use App\Utils\Utils;
use Tracy\ILogger;

class Logs
{
	public static function getLogs(\DateTimeImmutable $date, int $maxLines)
	{
		$logContents = [];
		foreach (SimpleLogger::getLogNames() as $logName) {
			$logContents[$logName] = SimpleLogger::getLogContent($logName, $date, $maxLines);
		}
		return $logContents;
	}

	public static function getTracyLogs(int $maxLines)
	{
		$logsContent = [];
		foreach (Utils::getClassConstants(ILogger::class) as $logName) {
			$logContent = self::getTracyLogContent($logName, $maxLines);
			if (count($logContent) > 0) {
				$logsContent[$logName] = $logContent;
			}
		}
		return $logsContent;
	}

	private static function getTracyLogContent(string $logName, int $maxLines): array
	{
		$tracyLogPath = Config::getTracyPath() . '/' . $logName . '.log';
		if (file_exists($tracyLogPath)) {
			$fileContent = Utils::tail($tracyLogPath, $maxLines);
			return explode(PHP_EOL, $fileContent);
		} else {
			return [];
		}
	}
}
