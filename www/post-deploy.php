<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

header('Content-Type: text/plain');

if (($_GET['password'] ?? null) !== \App\Config::CRON_PASSWORD) {
	http_response_code(\App\Web\MainPresenter::HTTP_FORBIDDEN);
	die('Invalid password');
}

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

$logArchiver = new \App\Maintenance\LogArchiver();

print('Creating backup of all logs...' . PHP_EOL);
printf('Logs were backed up and saved as \'%s\'.' . PHP_EOL, $logArchiver->createLogArchive());

print('Deleting old log files...' . PHP_EOL);
printf('Deleted %d old log files.' . PHP_EOL, $logArchiver->deleteOldFiles());

print('Deleting Tracy logs...' . PHP_EOL);
printf('Deleted %d Tracy logs.' . PHP_EOL, $logArchiver->deleteTracyLogs());
