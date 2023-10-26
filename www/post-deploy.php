<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

$tracyEmailSent = \App\Config::getTracyEmailPath();

printf('Cleaning temporary directory...' . PHP_EOL);
\Tracy\Debugger::timer('cleanuper');

$dirsToDelete = glob(\App\Config::FOLDER_TEMP . '/*', GLOB_ONLYDIR);
foreach ($dirsToDelete as $dirToDelete) {
	printf('Deleting directory "%s"...' . PHP_EOL, basename($dirToDelete));
	\Nette\Utils\FileSystem::delete($dirToDelete);
}

$diff = \Tracy\Debugger::timer('cleanuper');

printf(
	'Temporary directory was cleaned by deleting %d directories up in %s.' . PHP_EOL,
	count($dirsToDelete),
	\App\Utils\Formatter::seconds($diff),
);

if (file_exists($tracyEmailSent)) {
	\Nette\Utils\FileSystem::delete($tracyEmailSent);
	printf('Deleted Tracy\'s "%s".' . PHP_EOL, basename($tracyEmailSent));
}
