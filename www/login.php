<?php declare(strict_types=1);

require_once __DIR__ . '/../src/bootstrap.php';

if (\App\TelegramCustomWrapper\Login::hasRequiredGetParams($_GET)) {
	$login = new \App\TelegramCustomWrapper\Login($_GET);
	if ($login->isVerified()) {
		printf('Login was verified');
	} else {
		printf('Could not verify URL, try again');
	}
	dump($login);
} else {
	printf('Missing required data in GET parameter.');
}
