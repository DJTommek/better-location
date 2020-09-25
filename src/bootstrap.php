<?php declare(strict_types=1);

DEFINE('LOG_ID', rand(10000, 99999));

/**
 * Dummy autoloader which will try load files named and located exactly as namespace and class name.
 * Eg. class SomeDumbClass with namespace 'Some\Namespace\' will be located in libs/Some/Namespace/SomeDumbClass.php
 * Files without namespace has to be located directly in libs folder.
 *
 * @param string $className
 * @throws \Exception
 */
spl_autoload_register(function (string $className): void {
	$path = str_replace('\\', '/', $className);
	$file = str_replace('\\', '/', __DIR__) . '/libs/' . $path . '.php';
	if (file_exists($file)) {
		require_once $file;
	} else {
		throw new \Exception(sprintf('Class "%s" cannot be loaded, file "%s" does not exists.', $path, $file));
	}
});

$vendorAutoloadFilePath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($vendorAutoloadFilePath)) {
	require_once $vendorAutoloadFilePath;
} else {
	throw new \Exception(sprintf('Missing vendor autoload in "%s". Tip: Did you try to run "composer install" command?', $vendorAutoloadFilePath));
}

$localConfigFilePath = __DIR__ . '/../data/config.local.php';
if (file_exists($localConfigFilePath)) {
	require_once $localConfigFilePath;
} else {
	throw new \Exception(sprintf('Missing local config in "%s".', $localConfigFilePath));
}

Tracy\Debugger::enable(Config::DEVELOPMENT_IPS, Config::FOLDER_DATA . '/tracy-log/');
Tracy\Debugger::$strictMode = true;
Tracy\Debugger::$logSeverity = E_NOTICE | E_WARNING;

if (is_null(Config::TRACY_DEBUGGER_EMAIL) === false) {
	Tracy\Debugger::$email = Config::TRACY_DEBUGGER_EMAIL;
}

// Note: this might a lot of data to log but it's ok for alpha/beta phase. Probably should be removed in stable or production.
\Utils\DummyLogger::log(\Utils\DummyLogger::NAME_ALL_REQUESTS, $_SERVER);
