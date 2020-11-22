<?php declare(strict_types=1);

namespace App;

use Tracy\ILogger;

/**
 * Class DummyLogger which is not doing anything. Usefull for testing
 */
class DummyLogger implements ILogger
{
	public function log($value, $priority = ILogger::INFO)
	{
		// Do nothing
	}
}
