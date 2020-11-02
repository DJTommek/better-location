<?php declare(strict_types=1);

namespace App\Dashboard;

use App\Utils\DummyLogger;

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
}
